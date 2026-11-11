<?php

namespace App\Console\Commands;

use App\Services\PemeriksaKeamanan;
use Illuminate\Console\Command;

class PeriksaKeamanan extends Command
{
    protected $signature = 'keamanan:periksa';

    protected $description = 'Periksa kesalahan konfigurasi yang bisa membuka data pasien (jalankan sebelum dan sesudah deploy)';

    public function handle(PemeriksaKeamanan $pemeriksa): int
    {
        $hasil = $pemeriksa->periksa();

        if ($hasil === []) {
            $this->info('Tidak ada masalah keamanan yang terdeteksi.');

            return self::SUCCESS;
        }

        foreach ($hasil as $h) {
            $tanda = $h['level'] === 'bahaya' ? '<fg=red;options=bold>[BAHAYA]</>' : '<fg=yellow;options=bold>[WASPADA]</>';
            $this->line("{$tanda} {$h['judul']}");
            $this->line('         '.$h['teks']);
        }

        $bahaya = collect($hasil)->where('level', 'bahaya')->count();
        $this->newLine();
        $this->line(count($hasil).' temuan, '.$bahaya.' di antaranya bahaya.');

        return $bahaya > 0 ? self::FAILURE : self::SUCCESS;
    }
}
