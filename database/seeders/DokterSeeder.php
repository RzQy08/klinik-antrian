<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\Poli;
use Illuminate\Database\Seeder;

class DokterSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            'A' => ['dr. Andi Wijaya', 'dr. Siti Rahma'],       // Poli Umum -> 2 dokter (M/M/c, c=2)
            'B' => ['drg. Budi Santoso'],                        // Poli Gigi -> 1 dokter (M/M/1)
            'C' => ['dr. Citra Dewi, Sp.A'],                     // Poli Anak -> 1 dokter
            'D' => ['dr. Dewi Lestari, Sp.OG', 'Bidan Fitri'],   // Poli KIA -> 2 tenaga
        ];

        foreach ($mapping as $kodePoli => $namaDokters) {
            $poli = Poli::where('kode_poli', $kodePoli)->first();

            if (!$poli) {
                continue;
            }

            foreach ($namaDokters as $nama) {
                Dokter::firstOrCreate(
                    ['poli_id' => $poli->id, 'nama' => $nama],
                    ['no_sip' => 'SIP-' . strtoupper(uniqid()), 'aktif' => true]
                );
            }
        }
    }
}
