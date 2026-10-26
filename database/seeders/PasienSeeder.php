<?php

namespace Database\Seeders;

use App\Models\Pasien;
use Illuminate\Database\Seeder;

class PasienSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 200 pasien dummy, cukup buat simulasi kedatangan berulang
        Pasien::factory()->count(200)->create();
    }
}
