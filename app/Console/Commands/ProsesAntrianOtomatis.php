<?php

namespace App\Console\Commands;

use App\Services\PemanggilOtomatis;
use Illuminate\Console\Command;

class ProsesAntrianOtomatis extends Command
{
    protected $signature = 'antrian:otomatis';

    protected $description = 'Tandai pasien telat atau tidak hadir, lalu panggil pasien berikutnya untuk dokter yang kosong.';

    public function handle(): int
    {
        $h = PemanggilOtomatis::jalankan();

        $this->info("Telat: {$h['telat']}, tidak hadir: {$h['tidak_hadir']}, dipanggil otomatis: {$h['dipanggil']}");

        return self::SUCCESS;
    }
}
