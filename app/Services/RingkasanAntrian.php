<?php

namespace App\Services;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\Poli;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Ringkasan antrian hari ini untuk beranda publik.
 *
 * Satu paket data dipakai dua tempat: halaman beranda (data awal) dan endpoint JSON
 * yang dipoll panel antrian di hero. Jumlah kueri tetap, berapa pun jumlah poli.
 */
class RingkasanAntrian
{
    private const HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /** Dipakai bila belum ada data pelayanan selesai untuk poli itu (7 menit). */
    private const RATA_DEFAULT_DETIK = 420;

    /** Beberapa detik saja, cukup untuk meredam banyak pengunjung yang memoll bersamaan. */
    private const CACHE_DETIK = 2;

    public static function ambil(): array
    {
        if (app()->runningUnitTests()) {
            return self::susun();
        }

        return Cache::remember('beranda.ringkasan-antrian', self::CACHE_DETIK, fn () => self::susun());
    }

    public static function susun(): array
    {
        $sekarang = now();
        $hari = self::HARI[$sekarang->dayOfWeekIso - 1];
        $jam = $sekarang->format('H:i:s');

        // Kueri 1: poli aktif
        $polis = Poli::where('aktif', true)->orderBy('kode_poli')->get(['id', 'nama_poli', 'kode_poli']);

        // Kueri 2: nomor yang sedang dipanggil/dilayani hari ini, terbaru per poli
        $aktif = Antrian::hariIni()
            ->leftJoin('dokters', 'dokters.id', '=', 'antrians.dokter_id')
            ->whereIn('antrians.status', ['dipanggil', 'dilayani'])
            ->orderByDesc('antrians.waktu_dipanggil')
            ->get(['antrians.poli_id', 'antrians.nomor_antrian', 'antrians.status', 'dokters.nama as nama_dokter'])
            ->unique('poli_id')
            ->keyBy('poli_id');

        // Kueri 3: jumlah antrian per poli per status
        $hitung = Antrian::hariIni()
            ->selectRaw('poli_id, status, COUNT(*) as jumlah')
            ->groupBy('poli_id', 'status')
            ->get()
            ->groupBy('poli_id');

        // Kueri 4: nomor yang menunggu, urut sesuai pemanggilan (waktu daftar)
        $menunggu = Antrian::hariIni()->menunggu()
            ->orderBy('waktu_daftar')->orderBy('id')
            ->get(['poli_id', 'nomor_antrian'])
            ->groupBy('poli_id');

        // Kueri 5: rata-rata lama layanan 14 hari terakhir per poli (dasar perkiraan waktu tunggu)
        $selisih = Antrian::sqlSelisihDetik('waktu_mulai_dilayani', 'waktu_selesai');
        $rata = Antrian::untukAnalitik()
            ->where('tanggal', '>=', now()->subDays(14)->toDateString())
            ->whereNotNull('waktu_mulai_dilayani')
            ->whereNotNull('waktu_selesai')
            ->selectRaw("poli_id, AVG({$selisih}) as rata")
            ->groupBy('poli_id')
            ->pluck('rata', 'poli_id');

        // Kueri 6: jadwal praktik hari ini dari dokter yang aktif
        $jadwal = JadwalPraktik::where('hari', $hari)
            ->whereHas('dokter', fn ($q) => $q->where('aktif', true))
            ->with('dokter:id,poli_id,nama')
            ->get();

        $jadwalPerPoli = $jadwal->groupBy(fn (JadwalPraktik $j) => $j->dokter->poli_id);

        $baris = $polis->map(function (Poli $poli) use ($aktif, $hitung, $menunggu, $rata, $jadwalPerPoli, $jam) {
            $a = $aktif->get($poli->id);
            $status = $hitung->get($poli->id, collect())->pluck('jumlah', 'status');

            $jumlahMenunggu = (int) ($status['menunggu'] ?? 0);
            $jumlahSelesai = (int) ($status['selesai'] ?? 0);
            $jumlahTidakHadir = (int) ($status['tidak_hadir'] ?? 0);
            $total = $jumlahMenunggu + $jumlahSelesai + $jumlahTidakHadir
                + (int) ($status['dipanggil'] ?? 0) + (int) ($status['dilayani'] ?? 0); // yang batal tidak dihitung

            $rataDetik = (int) round((float) ($rata->get($poli->id) ?: self::RATA_DEFAULT_DETIK));
            $jadwalPoli = $jadwalPerPoli->get($poli->id, collect());

            return [
                'id' => $poli->id,
                'kode' => $poli->kode_poli,
                'nama' => $poli->nama_poli,
                'nomor' => $a?->nomor_antrian,
                'status_panggil' => $a?->status,
                'dokter' => $a?->nama_dokter ?? self::dokterTampil($jadwalPoli, $jam),
                'jam' => self::rentangJam($jadwalPoli),
                'praktik' => self::statusPraktik($jadwalPoli, $jam),
                'total' => $total,
                'menunggu' => $jumlahMenunggu,
                'selesai' => $jumlahSelesai,
                'persen' => $total > 0 ? (int) round(($jumlahSelesai + $jumlahTidakHadir) / $total * 100) : 0,
                'estimasi_menit' => $jumlahMenunggu > 0 ? max(1, (int) round($jumlahMenunggu * $rataDetik / 60)) : 0,
                'berikutnya' => $menunggu->get($poli->id, collect())->take(4)->pluck('nomor_antrian')->values()->all(),
                'url_layar' => route('antrian.display', $poli->id),
            ];
        })->values();

        return [
            'diperbarui' => $sekarang->toIso8601String(),
            'tanggal' => $sekarang->toDateString(),
            'ringkasan' => [
                'menunggu' => (int) $baris->sum('menunggu'),
                'selesai' => (int) $baris->sum('selesai'),
                'total' => (int) $baris->sum('total'),
                'poli' => $baris->count(),
                'dokter' => Dokter::where('aktif', true)->count(),
            ],
            'poli' => $baris->all(),
            'dokter' => self::dokterHariIni($jadwal, $polis, $jam),
        ];
    }

    /** Dokter yang punya jadwal hari ini, untuk bagian "Dokter praktik hari ini". */
    private static function dokterHariIni(Collection $jadwal, Collection $polis, string $jam): array
    {
        $namaPoli = $polis->pluck('nama_poli', 'id');

        return $jadwal
            ->filter(fn (JadwalPraktik $j) => $namaPoli->has($j->dokter->poli_id))
            ->groupBy('dokter_id')
            ->map(function (Collection $js) use ($namaPoli, $jam) {
                $dokter = $js->first()->dokter;

                return [
                    'nama' => $dokter->nama,
                    'inisial' => self::inisial($dokter->nama),
                    'poli' => $namaPoli->get($dokter->poli_id),
                    'jam' => self::rentangJam($js),
                    'praktik' => self::statusPraktik($js, $jam),
                ];
            })
            ->sortBy('poli')
            ->values()
            ->all();
    }

    private static function inisial(string $nama): string
    {
        $kata = preg_split('/[\s,]+/', trim(preg_replace('/^(dr|drg)\.?\s+/i', '', $nama)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(collect($kata)->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode(''));
    }

    private static function norm(?string $jam): string
    {
        $jam = (string) $jam;

        return strlen($jam) === 5 ? $jam.':00' : substr($jam, 0, 8);
    }

    private static function statusPraktik(Collection $jadwal, string $jam): string
    {
        if ($jadwal->isEmpty()) {
            return 'libur';
        }

        if ($jadwal->contains(fn ($j) => self::norm($j->jam_mulai) <= $jam && self::norm($j->jam_selesai) >= $jam)) {
            return 'berlangsung';
        }

        return $jadwal->contains(fn ($j) => self::norm($j->jam_mulai) > $jam) ? 'belum' : 'selesai';
    }

    private static function rentangJam(Collection $jadwal): ?string
    {
        if ($jadwal->isEmpty()) {
            return null;
        }

        $mulai = $jadwal->map(fn ($j) => substr(self::norm($j->jam_mulai), 0, 5))->min();
        $selesai = $jadwal->map(fn ($j) => substr(self::norm($j->jam_selesai), 0, 5))->max();

        return JamOperasional::tampil($mulai).' sampai '.JamOperasional::tampil($selesai);
    }

    /** Dokter yang ditampilkan di poli yang belum punya panggilan: yang sedang praktik, kalau tidak ada yang pertama. */
    private static function dokterTampil(Collection $jadwal, string $jam): ?string
    {
        if ($jadwal->isEmpty()) {
            return null;
        }

        $berlangsung = $jadwal->first(fn ($j) => self::norm($j->jam_mulai) <= $jam && self::norm($j->jam_selesai) >= $jam);

        return ($berlangsung ?? $jadwal->sortBy('jam_mulai')->first())->dokter->nama;
    }
}
