<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PoliSeeder::class,
            DokterSeeder::class,
            JadwalPraktikSeeder::class,
            PasienSeeder::class,
            AntrianSeeder::class, // paling akhir, karena butuh data poli/dokter/pasien
        ]);
    }
}
