<?php

namespace Database\Seeders;

use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Menghapus data yang dibuat oleh DataUjiSeeder saja.
 *
 *   php artisan db:seed --class=HapusDataUjiSeeder
 *
 * Yang dihapus:
 * - pasien dengan no_rm berawalan RM-UJI- beserta seluruh antriannya (cascade);
 * - akun dengan email berakhiran .uji@klinik.test.
 *
 * Poli, dokter, dan jadwal tidak dihapus karena mungkin sudah ada sebelum
 * seeder dijalankan / dipakai data lain.
 */
class HapusDataUjiSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Seeder ini menghapus data, jadi tidak boleh dijalankan di produksi.');

            return;
        }

        $pasien = Pasien::query()->where('no_rm', 'like', 'RM-UJI-%')->get();
        $pasienIds = $pasien->pluck('id');

        $jumlahAntrian = Antrian::query()->whereIn('pasien_id', $pasienIds)->count();
        Antrian::query()->whereIn('pasien_id', $pasienIds)->delete();

        $jumlahPasien = $pasien->count();
        Pasien::query()->whereIn('id', $pasienIds)->delete();

        $user = User::query()->where('email', 'like', '%.uji@klinik.test')->get();
        $jumlahUser = $user->count();
        User::query()->whereIn('id', $user->pluck('id'))->delete();

        $this->command?->info("Data uji dihapus: {$jumlahUser} akun, {$jumlahPasien} pasien, {$jumlahAntrian} antrian.");
        $this->command?->line('Data master (poli, dokter, jadwal) dan data pengguna lain tidak diubah.');
    }
}
