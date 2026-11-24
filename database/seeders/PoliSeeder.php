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
            ['nama_poli' => 'Poli THT', 'kode_poli' => 'E'],
            ['nama_poli' => 'Poli Penyakit Dalam', 'kode_poli' => 'F'],
            ['nama_poli' => 'Poli Mata', 'kode_poli' => 'G'],
            ['nama_poli' => 'Poli Kulit dan Kelamin', 'kode_poli' => 'H'],
        ];

        foreach ($polis as $poli) {
            Poli::firstOrCreate(
                ['kode_poli' => $poli['kode_poli']],
                $poli
            );
        }
    }
}
