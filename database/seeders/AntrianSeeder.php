<?php

namespace Database\Seeders;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AntrianSeeder extends Seeder
{
    /**
     * Jumlah hari ke belakang yang mau di-generate datanya.
     */
    private const JUMLAH_HARI = 30;

    /**
     * Jam operasional klinik.
     */
    private const JAM_BUKA = '08:00';
    private const JAM_TUTUP = '16:00';

    /**
     * Rata-rata kedatangan pasien per JAM untuk tiap poli (lambda).
     * Angka berbeda-beda supaya nanti ada variasi utilisasi (rho) antar poli
     * -> bagus buat pembahasan skripsi (poli mana yang overload).
     */
    private const LAMBDA_PER_JAM = [
        'A' => 9,   // Poli Umum - paling ramai
        'B' => 4,   // Poli Gigi
        'C' => 5,   // Poli Anak
        'D' => 6,   // Poli KIA
    ];

    /**
     * Rata-rata lama waktu pelayanan per pasien, dalam MENIT (1/mu).
     */
    private const RATA2_LAMA_LAYANAN_MENIT = [
        'A' => 8,
        'B' => 15,
        'C' => 10,
        'D' => 12,
    ];

    private Collection $pasienIds;

    public function run(): void
    {
        $this->pasienIds = Pasien::pluck('id');

        if ($this->pasienIds->isEmpty()) {
            $this->command->warn('Belum ada data pasien. Jalankan PasienSeeder dulu.');
            return;
        }

        $polis = Poli::with(['dokters' => fn ($q) => $q->where('aktif', true)])->get();

        foreach ($polis as $poli) {
            $dokters = $poli->dokters;

            if ($dokters->isEmpty()) {
                continue;
            }

            $lambdaPerJam = self::LAMBDA_PER_JAM[$poli->kode_poli] ?? 5;
            $rataLayananMenit = self::RATA2_LAMA_LAYANAN_MENIT[$poli->kode_poli] ?? 10;

            for ($h = self::JUMLAH_HARI; $h >= 1; $h--) {
                $tanggal = Carbon::today()->subDays($h);

                // Lewati hari Minggu (klinik tutup, sesuai jadwal praktik)
                if ($tanggal->isSunday()) {
                    continue;
                }

                $this->simulasiSatuHari($poli, $dokters, $tanggal, $lambdaPerJam, $rataLayananMenit);
            }

            $this->command->info("Selesai generate antrian untuk poli: {$poli->nama_poli}");
        }
    }

    /**
     * Simulasikan antrian untuk satu poli dalam satu hari, menggunakan model M/M/c:
     * - Kedatangan pasien: proses Poisson (selisih antar kedatangan = distribusi eksponensial)
     * - Waktu layanan: distribusi eksponensial
     * - Server (dokter) sejumlah c -> pasien dilayani dokter yang paling dulu kosong (FIFO)
     */
    private function simulasiSatuHari(
        Poli $poli,
        Collection $dokters,
        Carbon $tanggal,
        float $lambdaPerJam,
        float $rataLayananMenit
    ): void {
        $jamBuka = $tanggal->copy()->setTimeFromTimeString(self::JAM_BUKA);
        $jamTutup = $tanggal->copy()->setTimeFromTimeString(self::JAM_TUTUP);

        $lambdaPerMenit = $lambdaPerJam / 60;

        // Waktu server (dokter) bebas berikutnya -> awalnya semua bebas sejak jam buka
        $jumlahServer = $dokters->count();
        $waktuServerBebas = array_fill(0, $jumlahServer, $jamBuka->copy());

        $waktuSekarang = $jamBuka->copy();
        $urutan = 1;
        $rows = [];

        while (true) {
            // Selisih waktu kedatangan pasien berikutnya (distribusi eksponensial)
            $selisihMenit = -log(1 - mt_rand(1, 999999) / 1000000) / $lambdaPerMenit;
            $waktuSekarang = $waktuSekarang->copy()->addSeconds((int) round($selisihMenit * 60));

            if ($waktuSekarang->greaterThanOrEqualTo($jamTutup)) {
                break; // sudah lewat jam tutup, stop kedatangan hari ini
            }

            // Cari server (dokter) yang paling cepat kosong
            $indexServerTercepat = array_keys($waktuServerBebas, min($waktuServerBebas))[0];
            $mulaiDilayani = max($waktuSekarang, $waktuServerBebas[$indexServerTercepat]);

            // Lama layanan (distribusi eksponensial di sekitar rata-rata)
            $lamaLayananMenit = -log(1 - mt_rand(1, 999999) / 1000000) * $rataLayananMenit;
            $lamaLayananMenit = max(2, min($lamaLayananMenit, 60)); // batasi wajar: 2-60 menit

            $selesai = $mulaiDilayani->copy()->addSeconds((int) round($lamaLayananMenit * 60));
            $waktuServerBebas[$indexServerTercepat] = $selesai;

            $rows[] = [
                'pasien_id' => $this->pasienIds->random(),
                'poli_id' => $poli->id,
                'dokter_id' => $dokters[$indexServerTercepat]->id,
                'nomor_antrian' => $poli->kode_poli . str_pad((string) $urutan, 3, '0', STR_PAD_LEFT),
                'status' => 'selesai',
                'waktu_daftar' => $waktuSekarang,
                'waktu_dipanggil' => $mulaiDilayani->copy()->subSeconds(mt_rand(5, 30)),
                'waktu_mulai_dilayani' => $mulaiDilayani,
                'waktu_selesai' => $selesai,
                'estimasi_tunggu_detik' => null,
                'tanggal' => $tanggal->toDateString(),
                'created_at' => $waktuSekarang,
                'updated_at' => $selesai,
            ];

            $urutan++;
        }

        // Insert per hari sekaligus -> jauh lebih cepat daripada create() satu-satu
        foreach (array_chunk($rows, 200) as $chunk) {
            Antrian::insert($chunk);
        }
    }
}
