<?php

namespace App\Console\Commands;

use App\Models\Antrian;
use App\Models\Poli;
use App\Models\QueueMetric;
use App\Services\QueueTheoryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateQueueMetrics extends Command
{
    /**
     * Contoh pemakaian:
     *   php artisan queue-metrics:generate            -> generate untuk KEMARIN
     *   php artisan queue-metrics:generate 2026-07-20  -> generate untuk tanggal tertentu
     */
    protected $signature = 'queue-metrics:generate {tanggal? : Tanggal yang mau dihitung, format Y-m-d. Default: kemarin.}';

    protected $description = 'Hitung dan simpan snapshot metrik Queue Theory (lambda, mu, rho, Wq) harian per poli.';

    public function handle(): int
    {
        $tanggal = $this->argument('tanggal')
            ? Carbon::parse($this->argument('tanggal'))
            : Carbon::yesterday();

        $this->info("Menghitung metrik queue theory untuk tanggal: {$tanggal->toDateString()}");

        $polis = Poli::where('aktif', true)->get();
        $jumlahBerhasil = 0;

        foreach ($polis as $poli) {
            $berhasil = $this->prosesSatuPoli($poli, $tanggal);

            if ($berhasil) {
                $jumlahBerhasil++;
            }
        }

        $this->info("Selesai. {$jumlahBerhasil} dari {$polis->count()} poli berhasil dihitung.");

        return self::SUCCESS;
    }

    private function prosesSatuPoli(Poli $poli, Carbon $tanggal): bool
    {
                // Satu query agregat: jumlah sampel dan rata-rata waktu tunggu (tanpa limpahan)
        $tunggu = Antrian::sqlSelisihDetik('waktu_daftar', 'waktu_mulai_dilayani');

        $statistik = Antrian::untukPoli($poli->id)
            ->untukAnalitik()
            ->padaTanggal($tanggal)
            ->selectRaw("COUNT(*) as jumlah, AVG({$tunggu}) as rata_tunggu")
            ->first();

        $jumlahSampel = (int) $statistik->jumlah;

        if ($jumlahSampel === 0) {
            $this->warn("  [{$poli->kode_poli}] {$poli->nama_poli}: tidak ada data, dilewati.");

            return false;
        }

        try {
            $service = QueueTheoryService::dariData(
                poliId: $poli->id,
                dari: $tanggal,
                sampai: $tanggal,
            );
        } catch (\InvalidArgumentException $e) {
            $this->warn("  [{$poli->kode_poli}] {$poli->nama_poli}: {$e->getMessage()}");

            return false;
        }

        $wqAktualDetik = $statistik->rata_tunggu !== null ? (float) $statistik->rata_tunggu : null;
        $wqAktualMenit = $wqAktualDetik !== null ? round($wqAktualDetik / 60, 2) : null;

        $stabil = $service->stabil();
        $wqTeoriMenit = $stabil ? round($service->wqMenit(), 2) : null;

        QueueMetric::updateOrCreate(
            ['poli_id' => $poli->id, 'tanggal' => $tanggal->toDateString()],
            [
                'lambda' => $service->lambda,
                'mu' => $service->mu,
                'rho' => round($service->rho(), 4),
                'wq_teori_menit' => $wqTeoriMenit,
                'wq_aktual_menit' => $wqAktualMenit,
            ]
        );

        $status = $stabil ? "rho={$service->rho()}" : 'TIDAK STABIL';
        $this->line("  [{$poli->kode_poli}] {$poli->nama_poli}: {$jumlahSampel} sampel, {$status}");

        return true;
    }
}
