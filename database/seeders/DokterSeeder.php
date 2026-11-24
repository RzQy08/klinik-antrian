<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\Poli;
use Illuminate\Database\Seeder;

class DokterSeeder extends Seeder
{
    /** Dokter menurut kode poli. Nama yang sudah ada tidak diganti supaya akun dan riwayatnya tetap. */
    public const DOKTER = [
        'A' => ['dr. Andi Wijaya', 'dr. Siti Rahma'],               // Poli Umum -> 2 dokter (M/M/c, c=2)
        'B' => ['drg. Budi Santoso'],                                // Poli Gigi -> 1 dokter (M/M/1)
        'C' => ['dr. Citra Dewi, Sp.A'],                             // Poli Anak -> 1 dokter
        'D' => ['dr. Dewi Lestari, Sp.OG', 'Bidan Fitri'],           // Poli KIA -> 2 tenaga
        'E' => ['dr. Rina Kusumawati, Sp.THT-KL'],                   // Poli THT
        'F' => ['dr. Fajar Nugraha, Sp.PD', 'dr. Maria Ulfa, Sp.PD'], // Poli Penyakit Dalam -> 2 dokter
        'G' => ['dr. Bambang Hartanto, Sp.M'],                       // Poli Mata
        'H' => ['dr. Larasati Putri, Sp.DV'],                        // Poli Kulit dan Kelamin
    ];

    public function run(): void
    {
        $urut = 0;

        foreach (self::DOKTER as $kodePoli => $namaDokters) {
            $poli = Poli::where('kode_poli', $kodePoli)->first();

            foreach ($namaDokters as $nama) {
                $urut++;

                if (! $poli) {
                    continue;
                }

                $dokter = Dokter::firstOrCreate(
                    ['poli_id' => $poli->id, 'nama' => $nama],
                    ['no_sip' => self::nomorSip($urut), 'aktif' => true]
                );

                // SIP acak bawaan versi lama (SIP-6A5F74E765628) diganti nomor yang berformat rapi
                if (preg_match('/^SIP-[0-9A-F]{13}$/', (string) $dokter->no_sip) === 1) {
                    $dokter->update(['no_sip' => self::nomorSip($urut)]);
                }
            }
        }
    }

    /** Nomor SIP fiktif yang rapi dan unik, mis. 446/0007/SIP-DOK/2024. */
    public static function nomorSip(int $urut): string
    {
        return sprintf('446/%04d/SIP-DOK/2024', $urut);
    }
}
