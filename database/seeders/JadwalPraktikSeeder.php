<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\JadwalPraktik;
use Illuminate\Database\Seeder;

class JadwalPraktikSeeder extends Seeder
{
    /**
     * Jadwal dokter spesialis: [hari, jam mulai, jam selesai, kuota].
     * Dokter yang tidak ada di sini (poli dasar) praktik Senin-Sabtu 08.00-16.00 dengan kuota 40.
     *
     * @var array<string, array{0: array<int, string>, 1: string, 2: string, 3: int}>
     */
    public const SPESIALIS = [
        'dr. Rina Kusumawati, Sp.THT-KL' => [['senin', 'rabu', 'jumat'], '09:00', '14:00', 25],
        'dr. Fajar Nugraha, Sp.PD' => [['senin', 'selasa', 'rabu', 'kamis'], '08:00', '15:00', 30],
        'dr. Maria Ulfa, Sp.PD' => [['selasa', 'kamis', 'jumat', 'sabtu'], '08:00', '14:00', 30],
        'dr. Bambang Hartanto, Sp.M' => [['selasa', 'kamis', 'sabtu'], '09:00', '14:00', 25],
        'dr. Larasati Putri, Sp.DV' => [['senin', 'selasa', 'rabu', 'kamis', 'jumat'], '10:00', '15:00', 25],
    ];

    public function run(): void
    {
        $hariKerja = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

        Dokter::all()->each(function (Dokter $dokter) use ($hariKerja) {
            [$hari, $mulai, $selesai, $kuota] = self::SPESIALIS[$dokter->nama] ?? [$hariKerja, '08:00', '16:00', 40];

            foreach ($hari as $namaHari) {
                JadwalPraktik::firstOrCreate(
                    ['dokter_id' => $dokter->id, 'hari' => $namaHari],
                    [
                        'jam_mulai' => $mulai.':00',
                        'jam_selesai' => $selesai.':00',
                        'kuota' => $kuota,
                    ]
                );
            }
        });
    }
}
