<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\JadwalPraktik;
use Illuminate\Database\Seeder;

class JadwalPraktikSeeder extends Seeder
{
    public function run(): void
    {
        // Semua dokter praktik Senin-Sabtu, jam 08:00 - 16:00, kuota 40 pasien/sesi
        $hariKerja = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

        Dokter::all()->each(function (Dokter $dokter) use ($hariKerja) {
            foreach ($hariKerja as $hari) {
                JadwalPraktik::firstOrCreate(
                    ['dokter_id' => $dokter->id, 'hari' => $hari],
                    [
                        'jam_mulai' => '08:00:00',
                        'jam_selesai' => '16:00:00',
                        'kuota' => 40,
                    ]
                );
            }
        });
    }
}
