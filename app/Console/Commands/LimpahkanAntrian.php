<?php

namespace App\Console\Commands;

use App\Models\Antrian;
use App\Models\Poli;
use App\Services\PesanAntrian;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LimpahkanAntrian extends Command
{
    /**
     * Contoh:
     *   php artisan antrian:limpahkan                      -> sisa kemarin ke hari ini
     *   php artisan antrian:limpahkan 2026-10-19 --ke=2026-10-20
     *   php artisan antrian:limpahkan --tanpa-wa           -> tanpa kirim WhatsApp
     */
    protected $signature = 'antrian:limpahkan
                            {dari? : Tanggal antrian sisa (Y-m-d). Default: kemarin}
                            {--ke= : Tanggal tujuan (Y-m-d). Default: hari ini}
                            {--tanpa-wa : Jangan kirim WhatsApp ke pasien}';

    protected $description = 'Pindahkan antrian yang belum terpanggil ke hari berikutnya dengan nomor baru yang berurutan.';

    public function handle(): int
    {
        $dari = $this->argument('dari') ? Carbon::parse($this->argument('dari'))->startOfDay() : Carbon::yesterday();
        $ke = $this->option('ke') ? Carbon::parse($this->option('ke'))->startOfDay() : Carbon::today();

        if ($ke->lessThanOrEqualTo($dari)) {
            $this->error('Tanggal tujuan harus setelah tanggal asal.');

            return self::FAILURE;
        }

        $statusDipindah = (array) config('operasional.limpahan_status', ['menunggu']);
        $kirimWa = ! $this->option('tanpa-wa');

        $this->info("Memindahkan antrian sisa {$dari->toDateString()} ke {$ke->toDateString()}");

        $baris = [];
        $total = 0;

        foreach (Poli::where('aktif', true)->orderBy('kode_poli')->get() as $poli) {
            $sisa = Antrian::untukPoli($poli->id)
                ->padaTanggal($dari)
                ->whereIn('status', $statusDipindah)
                ->whereNull('dialihkan_at')
                ->with('pasien')
                ->orderBy('waktu_daftar')
                ->get();

            if ($sisa->isEmpty()) {
                $this->line("  [{$poli->kode_poli}] {$poli->nama_poli}: tidak ada sisa.");

                continue;
            }

            // Kalau tanggal tujuan sudah punya antrian, penomoran melanjutkan
            $urutAwal = Antrian::where('poli_id', $poli->id)
                ->padaTanggal($ke)
                ->count();

            $pasangan = DB::transaction(function () use ($sisa, $poli, $dari, $ke, $urutAwal) {
                $hasil = [];

                foreach ($sisa->values() as $i => $lama) {
                    $baru = Antrian::create([
                        'pasien_id' => $lama->pasien_id,
                        'poli_id' => $poli->id,
                        'nomor_antrian' => $poli->kode_poli . str_pad((string) ($urutAwal + $i + 1), 3, '0', STR_PAD_LEFT),
                        'status' => 'menunggu',
                        // Dibuat tepat setelah tengah malam supaya selalu berada di depan pendaftar baru,
                        // dan urutan lama dipertahankan (yang menunggu lebih lama tetap lebih dulu).
                        'waktu_daftar' => $ke->copy()->startOfDay()->addSeconds($i),
                        'tanggal' => $ke->toDateString(),
                        'tanggal_asal' => $dari->toDateString(),
                        'nomor_lama' => $lama->nomor_antrian,
                        'jumlah_panggil' => 0,
                    ]);

                    $lama->update(['dialihkan_at' => now()]);

                    $hasil[] = [$lama, $baru];
                }

                return $hasil;
            });

            foreach ($pasangan as [$lama, $baru]) {
                $baris[] = [$poli->nama_poli, $lama->nomor_antrian, $baru->nomor_antrian, $lama->pasien?->nama ?? '-'];
                $total++;

                if ($kirimWa && $lama->pasien?->no_hp) {
                    $this->kirimWhatsApp($lama, $baru, $poli, $dari, $ke);
                }
            }
        }

        if ($total > 0) {
            $this->newLine();
            $this->table(['Poli', 'Nomor lama', 'Nomor baru', 'Pasien'], $baris);
        }

        $this->info("Selesai: {$total} antrian dipindahkan.");

        return self::SUCCESS;
    }

    private function kirimWhatsApp(Antrian $lama, Antrian $baru, Poli $poli, Carbon $dari, Carbon $ke): void
    {
        try {
            $pesan = PesanAntrian::limpahan(
                $lama->pasien->nama,
                $baru->nomor_antrian,
                $lama->nomor_antrian,
                $poli->nama_poli,
                $dari->copy()->locale('id')->translatedFormat('l, j F Y'),
                $ke->copy()->locale('id')->translatedFormat('l, j F Y'),
            );

            app(WhatsAppService::class)->kirim($lama->pasien->no_hp, $pesan);
        } catch (\Throwable $e) {
            $this->warn("  WhatsApp ke {$lama->pasien->nama} gagal: {$e->getMessage()}");
        }
    }
}
