<?php

namespace App\Livewire;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use Livewire\Component;

/**
 * Panel kerja dokter: pasien yang sedang dilayani, yang sudah dipanggil untuknya, dan ringkasan hari ini.
 * Diperbarui sendiri tiap 5 detik, jadi pasien yang dipanggil petugas langsung muncul tanpa muat ulang.
 *
 * Aksi Livewire tidak melewati middleware role, jadi setiap aksi memastikan penggunanya dokter
 * dan hanya boleh mengubah pasien miliknya sendiri.
 */
class PanelDokter extends Component
{
    // Jumlah pasien "dipanggil" pada pembaruan terakhir, untuk memicu bunyi saat ada panggilan baru
    public int $terakhirDipanggil = 0;

    public ?string $pesan = null;
    public string $tipePesan = 'success';
    public int $pesanId = 0;

    public function mount(): void
    {
        $this->terakhirDipanggil = $this->hitungDipanggil();
    }

    /** Dipanggil polling. Bila ada pasien baru dipanggil, browser diberi tahu supaya membunyikan nada. */
    public function segarkan(): void
    {
        $sekarang = $this->hitungDipanggil();

        if ($sekarang > $this->terakhirDipanggil) {
            $this->dispatch('ada-panggilan');
        }

        $this->terakhirDipanggil = $sekarang;
    }

    public function mulai(int $id): void
    {
        $dokter = $this->dokter();
        $antrian = Antrian::where('dokter_id', $dokter->id)->find($id);

        if (! $antrian) {
            $this->toast('error', 'Pasien ini bukan pasien Anda.');

            return;
        }

        // Satu dokter melayani satu pasien pada satu waktu, sesuai model antrian M/M/c
        if (Antrian::where('dokter_id', $dokter->id)->hariIni()->where('status', 'dilayani')->exists()) {
            $this->toast('error', 'Selesaikan dulu pasien yang sedang dilayani sebelum memulai yang berikutnya.');

            return;
        }

        $diubah = Antrian::whereKey($antrian->id)->where('dokter_id', $dokter->id)->where('status', 'dipanggil')
            ->update(['status' => 'dilayani', 'waktu_mulai_dilayani' => now()]);

        $diubah
            ? $this->toast('success', "Pelayanan {$antrian->nomor_antrian} dimulai.")
            : $this->toast('error', "{$antrian->nomor_antrian} sudah tidak berstatus dipanggil. Mungkin diubah petugas.");

        $this->terakhirDipanggil = $this->hitungDipanggil();
    }

    public function selesai(int $id): void
    {
        $dokter = $this->dokter();
        $antrian = Antrian::where('dokter_id', $dokter->id)->find($id);

        if (! $antrian) {
            $this->toast('error', 'Pasien ini bukan pasien Anda.');

            return;
        }

        $diubah = Antrian::whereKey($antrian->id)->where('dokter_id', $dokter->id)->where('status', 'dilayani')
            ->update(['status' => 'selesai', 'waktu_selesai' => now()]);

        $diubah
            ? $this->toast('success', "Pelayanan {$antrian->nomor_antrian} selesai. Terima kasih, dok.")
            : $this->toast('error', "{$antrian->nomor_antrian} tidak sedang dilayani.");
    }

    public function tidakHadir(int $id): void
    {
        $dokter = $this->dokter();
        $antrian = Antrian::where('dokter_id', $dokter->id)->find($id);

        if (! $antrian) {
            $this->toast('error', 'Pasien ini bukan pasien Anda.');

            return;
        }

        $diubah = Antrian::whereKey($antrian->id)->where('dokter_id', $dokter->id)->where('status', 'dipanggil')
            ->update(['status' => 'tidak_hadir']);

        $diubah
            ? $this->toast('success', "{$antrian->nomor_antrian} ditandai tidak hadir. Petugas bisa memanggilnya ulang.")
            : $this->toast('error', "{$antrian->nomor_antrian} sudah tidak berstatus dipanggil.");

        $this->terakhirDipanggil = $this->hitungDipanggil();
    }

    public function render()
    {
        $dokter = $this->dokter();

        $milikSaya = fn () => Antrian::where('dokter_id', $dokter->id)->hariIni();

        $sedang = $milikSaya()->where('status', 'dilayani')->with('pasien')->orderBy('waktu_mulai_dilayani')->get();
        $dipanggil = $milikSaya()->where('status', 'dipanggil')->with('pasien')->orderBy('waktu_dipanggil')->get();

        // Yang dikerjakan lebih dulu: pasien yang sedang dilayani, kalau tidak ada maka yang pertama dipanggil
        $fokus = $sedang->first() ?? $dipanggil->first();

        $layanan = Antrian::sqlSelisihDetik('waktu_mulai_dilayani', 'waktu_selesai');
        $rata = $milikSaya()->selesai()->selectRaw("AVG({$layanan}) as rata")->value('rata');

        $namaHari = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'][now()->dayOfWeek];
        $jadwal = JadwalPraktik::where('dokter_id', $dokter->id)->where('hari', $namaHari)->first();
        $selesaiHariIni = $milikSaya()->selesai()->count();

        return view('livewire.panel-dokter', [
            'dokter' => $dokter,
            'fokus' => $fokus,
            'fokusDilayani' => (bool) $sedang->first(),
            'sedangSekarang' => $sedang->isNotEmpty(),
            'lainnya' => $dipanggil->reject(fn ($a) => $fokus && $a->id === $fokus->id)->values(),
            'berikutnya' => Antrian::untukPoli($dokter->poli_id)->hariIni()->menunggu()
                ->with('pasien:id,nama')->orderBy('waktu_daftar')->limit(4)
                ->get(['id', 'pasien_id', 'nomor_antrian', 'tanggal_asal', 'nomor_lama']),
            'menungguDiPoli' => Antrian::untukPoli($dokter->poli_id)->hariIni()->menunggu()->count(),
            'selesaiTerakhir' => $milikSaya()->selesai()->with('pasien:id,nama')->orderByDesc('waktu_selesai')->limit(6)->get(),
            'selesaiHariIni' => $selesaiHariIni,
            'tidakHadirHariIni' => $milikSaya()->where('status', 'tidak_hadir')->count(),
            'rataMenit' => $rata !== null ? (int) round($rata / 60) : null,
            'jadwal' => $jadwal,
            'kuotaPersen' => $jadwal && $jadwal->kuota > 0 ? min(100, (int) round($selesaiHariIni / $jadwal->kuota * 100)) : 0,
        ]);
    }

    private function hitungDipanggil(): int
    {
        return Antrian::where('dokter_id', $this->dokter()->id)->hariIni()->where('status', 'dipanggil')->count();
    }

    private function dokter(): Dokter
    {
        $user = auth()->user();

        abort_unless($user && $user->isDokter(), 403);
        abort_unless($user->dokter, 403, 'Akun ini tidak terhubung dengan data dokter.');

        return $user->dokter;
    }

    private function toast(string $tipe, string $teks): void
    {
        $this->tipePesan = $tipe;
        $this->pesan = $teks;
        $this->pesanId++;
    }
}
