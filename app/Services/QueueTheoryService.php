<?php

namespace App\Services;

use App\Models\Antrian;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Service untuk menghitung metrik antrian berdasarkan Queue Theory
 * menggunakan model M/M/c (Multi-server Poisson arrival, Exponential service).
 *
 * M/M/1 otomatis tercakup sebagai kasus khusus ketika c = 1.
 *
 * Notasi:
 *  - lambda (λ) : rata-rata kedatangan pasien per jam
 *  - mu (μ)     : rata-rata pasien yang bisa dilayani SATU dokter per jam
 *  - c          : jumlah dokter/server yang melayani secara paralel
 *  - rho (ρ)    : tingkat utilisasi sistem = λ / (c·μ)
 */
class QueueTheoryService
{
    public function __construct(
        public readonly float $lambda,
        public readonly float $mu,
        public readonly int $c = 1,
    ) {
        if ($lambda <= 0 || $mu <= 0) {
            throw new InvalidArgumentException('Lambda dan mu harus lebih besar dari 0.');
        }

        if ($c < 1) {
            throw new InvalidArgumentException('Jumlah server (c) minimal 1.');
        }
    }

    /**
     * Bangun service ini langsung dari data historis di tabel `antrians`.
     *
     * @param int    $poliId
     * @param Carbon $dari
     * @param Carbon $sampai
     * @param float  $jamOperasionalPerHari  dipakai untuk konversi lambda ke satuan per jam
     */
    public static function dariData(
        int $poliId,
        Carbon $dari,
        Carbon $sampai,
        float $jamOperasionalPerHari = 8.0
    ): self {
        $layanan = Antrian::sqlSelisihDetik('waktu_mulai_dilayani', 'waktu_selesai');

        // Semua hitungan dikerjakan database (hasilnya satu baris), bukan memuat ribuan baris ke PHP.
        // untukAnalitik() = status selesai dan bukan limpahan dari hari lain.
        $statistik = Antrian::query()
            ->untukPoli($poliId)
            ->untukAnalitik()
            ->antaraTanggal($dari, $sampai)
            ->selectRaw("COUNT(*) as jumlah, COUNT(DISTINCT dokter_id) as dokter, COUNT(DISTINCT tanggal) as hari_aktif, AVG({$layanan}) as rata_layanan")
            ->first();

        return self::dariStatistik(
            jumlah: (int) $statistik->jumlah,
            rataLamaLayananDetik: $statistik->rata_layanan !== null ? (float) $statistik->rata_layanan : null,
            jumlahDokter: (int) $statistik->dokter,
            dari: $dari,
            sampai: $sampai,
            jamOperasionalPerHari: $jamOperasionalPerHari,
            hariAktif: (int) $statistik->hari_aktif,
        );
    }

    /**
     * Bangun service dari angka ringkasan yang sudah dihitung database.
     * Dipisah dari dariData() supaya halaman analitik nanti bisa memakai satu query untuk semua poli.
     */
    public static function dariStatistik(
        int $jumlah,
        ?float $rataLamaLayananDetik,
        int $jumlahDokter,
        Carbon $dari,
        Carbon $sampai,
        float $jamOperasionalPerHari = 8.0,
        ?int $hariAktif = null
    ): self {
        if ($jumlah === 0) {
            throw new InvalidArgumentException('Tidak ada data antrian selesai pada rentang tanggal ini.');
        }

        if ($rataLamaLayananDetik === null || $rataLamaLayananDetik <= 0) {
            throw new InvalidArgumentException('Data antrian selesai belum memuat waktu mulai dan selesai layanan.');
        }

        // Hanya hari yang benar-benar ada pelayanan yang dihitung. Hari libur (misalnya Minggu) tidak
        // boleh ikut membagi, kalau tidak lambda terlalu kecil dan rho/Wq teoritis ikut meleset.
        // Tanpa data hari aktif, dipakai seluruh hari pada rentang (perilaku lama).
        $jumlahHari = ($hariAktif !== null && $hariAktif > 0)
            ? $hariAktif
            : (int) round(abs($dari->copy()->startOfDay()->diffInDays($sampai->copy()->startOfDay()))) + 1;
        $totalJamOperasional = $jumlahHari * $jamOperasionalPerHari;

        // lambda = total kedatangan / total jam operasional pada hari aktif
        $lambda = $jumlah / $totalJamOperasional;

        // mu = 1 / rata-rata lama layanan (dalam jam), per SATU dokter
        $mu = 3600 / $rataLamaLayananDetik;

        // jumlah dokter yang benar-benar melayani di rentang ini
        $c = max(1, $jumlahDokter);

        return new self(round($lambda, 4), round($mu, 4), $c);
    }

    /**
     * Tingkat utilisasi sistem (rho).
     * rho >= 1 berarti sistem TIDAK STABIL (antrian akan terus menumpuk).
     */
    public function rho(): float
    {
        return $this->lambda / ($this->c * $this->mu);
    }

    public function stabil(): bool
    {
        return $this->rho() < 1;
    }

    /**
     * Probabilitas sistem kosong (P0) - dibutuhkan untuk rumus Erlang C.
     */
    public function p0(): float
    {
        $rho = $this->rho();
        $a = $this->lambda / $this->mu; // offered load (a = c * rho)

        $jumlah = 0.0;
        for ($n = 0; $n < $this->c; $n++) {
            $jumlah += ($a ** $n) / $this->faktorial($n);
        }

        $sukuTerakhir = ($a ** $this->c) / ($this->faktorial($this->c) * (1 - $rho));

        return 1 / ($jumlah + $sukuTerakhir);
    }

    /**
     * Erlang C formula: probabilitas pasien yang datang harus MENUNGGU
     * (semua dokter sedang sibuk).
     */
    public function probabilitasMenunggu(): float
    {
        $rho = $this->rho();
        $a = $this->lambda / $this->mu;

        $pembilang = ($a ** $this->c) / ($this->faktorial($this->c) * (1 - $rho));

        return $pembilang * $this->p0();
    }

    /**
     * Wq: rata-rata waktu TUNGGU dalam antrian (sebelum dilayani), dalam JAM.
     */
    public function wq(): float
    {
        $this->pastikanStabil();

        return $this->probabilitasMenunggu() / ($this->c * $this->mu - $this->lambda);
    }

    /**
     * Wq dalam satuan MENIT - lebih intuitif buat ditampilkan ke pasien.
     */
    public function wqMenit(): float
    {
        return $this->wq() * 60;
    }

    /**
     * Lq: rata-rata JUMLAH pasien yang sedang mengantre (belum dilayani).
     */
    public function lq(): float
    {
        return $this->probabilitasMenunggu() * $this->rho() / (1 - $this->rho());
    }

    /**
     * W: rata-rata waktu TOTAL pasien dalam sistem (menunggu + dilayani), dalam JAM.
     */
    public function w(): float
    {
        return $this->wq() + (1 / $this->mu);
    }

    public function wMenit(): float
    {
        return $this->w() * 60;
    }

    /**
     * L: rata-rata JUMLAH pasien dalam sistem (mengantre + sedang dilayani).
     */
    public function l(): float
    {
        return $this->lambda * $this->w();
    }

    /**
     * Ringkasan semua metrik sekaligus - enak dipakai buat ditampilkan di dashboard.
     */
    public function ringkasan(): array
    {
        $this->pastikanStabil();

        return [
            'lambda_per_jam' => round($this->lambda, 3),
            'mu_per_jam' => round($this->mu, 3),
            'jumlah_server' => $this->c,
            'rho' => round($this->rho(), 4),
            'stabil' => $this->stabil(),
            'probabilitas_menunggu' => round($this->probabilitasMenunggu(), 4),
            'wq_menit' => round($this->wqMenit(), 2),
            'w_menit' => round($this->wMenit(), 2),
            'lq_pasien' => round($this->lq(), 2),
            'l_pasien' => round($this->l(), 2),
        ];
    }

    /**
     * Estimasi waktu tunggu untuk pasien yang BARU SAJA daftar,
     * berdasarkan posisi antrian saat ini + Wq teoritis sebagai baseline.
     *
     * Ini yang dipakai buat tampilkan "estimasi tunggu: X menit" ke pasien.
     */
    public function estimasiTungguDetik(int $posisiDalamAntrian): int
    {
        // pendekatan sederhana: posisi antrian dibagi jumlah server,
        // dikali rata-rata waktu layanan per pasien
        $estimasiJam = ($posisiDalamAntrian / $this->c) * (1 / $this->mu);

        return (int) round($estimasiJam * 3600);
    }

    /**
     * Salinan model dengan jumlah dokter berbeda (λ dan μ per dokter tetap sama).
     * Dipakai untuk simulasi "bagaimana kalau dokter ditambah/dikurangi".
     */
    public function denganDokter(int $c): self
    {
        return new self($this->lambda, $this->mu, $c);
    }

    /**
     * Simulasi jumlah dokter $dari sampai $sampai: utilisasi dan waktu tunggu teoritis tiap skenario.
     * Skenario yang tidak stabil (rho >= 1) memiliki wq_menit null.
     *
     * @return array<int, array{c:int, rho:float, stabil:bool, wq_menit:?float, peluang_menunggu:?float}>
     */
    public function simulasiDokter(int $dari, int $sampai): array
    {
        $hasil = [];

        for ($c = max(1, $dari); $c <= $sampai; $c++) {
            $skenario = $this->denganDokter($c);
            $stabil = $skenario->stabil();

            $hasil[$c] = [
                'c' => $c,
                'rho' => round($skenario->rho(), 4),
                'stabil' => $stabil,
                'wq_menit' => $stabil ? round($skenario->wqMenit(), 2) : null,
                'peluang_menunggu' => $stabil ? round($skenario->probabilitasMenunggu(), 4) : null,
            ];
        }

        return $hasil;
    }

    /**
     * Jumlah dokter paling sedikit yang membuat sistem stabil, utilisasi tidak melebihi $rhoMaks,
     * dan rata-rata waktu tunggu tidak melebihi $targetWqMenit. Null kalau $maksDokter pun belum cukup.
     */
    public function rekomendasiDokter(float $targetWqMenit = 10.0, float $rhoMaks = 0.85, int $maksDokter = 10): ?int
    {
        for ($c = 1; $c <= $maksDokter; $c++) {
            $skenario = $this->denganDokter($c);

            if ($skenario->stabil() && $skenario->rho() <= $rhoMaks && $skenario->wqMenit() <= $targetWqMenit) {
                return $c;
            }
        }

        return null;
    }

    private function faktorial(int $n): float
    {
        if ($n <= 1) {
            return 1.0;
        }

        return $n * $this->faktorial($n - 1);
    }

    private function pastikanStabil(): void
    {
        if (!$this->stabil()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Sistem tidak stabil (rho = %.3f >= 1). Kedatangan pasien melebihi kapasitas layanan. '
                    . 'Tambah jumlah dokter/server atau kurangi lambda.',
                    $this->rho()
                )
            );
        }
    }
}