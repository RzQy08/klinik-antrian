<?php

namespace Database\Seeders;

use App\Models\Poli;
use Illuminate\Database\Seeder;

class PoliSeeder extends Seeder
{
    public function run(): void
    {
        $polis = [
            ['nama_poli' => 'Poli Umum', 'kode_poli' => 'A'],
            ['nama_poli' => 'Poli Gigi', 'kode_poli' => 'B'],
            ['nama_poli' => 'Poli Anak', 'kode_poli' => 'C'],
            ['nama_poli' => 'Poli KIA', 'kode_poli' => 'D'],
        ];

        foreach ($polis as $poli) {
            Poli::firstOrCreate(
                ['kode_poli' => $poli['kode_poli']],
                $poli
            );
        }
    }
}
