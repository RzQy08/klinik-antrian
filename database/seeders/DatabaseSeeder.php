<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data master (poli, dokter, jadwal) aman untuk semua lingkungan
        $this->call([
            PoliSeeder::class,
            DokterSeeder::class,
            JadwalPraktikSeeder::class,
            JadwalRumahSakitSeeder::class,
        ]);

        // Pasien dan antrian contoh bersifat palsu, jadi tidak dibuat di server produksi
        if (app()->isProduction()) {
            $this->command?->warn('Produksi: pasien dan antrian contoh dilewati.');

            return;
        }

        $this->call([
            PasienSeeder::class,
            AntrianSeeder::class, // paling akhir, karena butuh data poli/dokter/pasien
        ]);
    }
}
