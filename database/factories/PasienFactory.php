<?php

namespace Database\Factories;

use Carbon\Carbon;
use Database\Seeders\DataPasienRealistis;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;

class PasienFactory extends Factory
{
    protected $model = \App\Models\Pasien::class;

    /** Kode kecamatan di Kota Bandung (327301-327330), diputar supaya NIK tersebar. */
    private const KECAMATAN = 30;

    private static int $urut = 0;

    private static ?Generator $fakerIndonesia = null;

    /** Nama dan alamat berbahasa Indonesia, satu generator dipakai bersama supaya unique() tetap berlaku. */
    protected function withFaker(): Generator
    {
        return self::$fakerIndonesia ??= FakerFactory::create('id_ID');
    }

    public function definition(): array
    {
        self::$urut++;

        $jk = $this->faker->randomElement(['L', 'P']);
        $lahir = Carbon::instance($this->faker->dateTimeBetween('-70 years', '-1 years'));

        // Nomor urut 9001-9999 dipakai pertama, lalu kecamatan berganti: kombinasinya tidak pernah berulang
        $kecamatan = 1 + (intdiv(self::$urut - 1, 999) % self::KECAMATAN);
        $urut = 9001 + ((self::$urut - 1) % 999);

        $depan = $jk === 'L' ? $this->faker->firstNameMale() : $this->faker->firstNameFemale();

        return [
            'no_rm' => 'RM-'.$this->faker->unique()->numerify('######'),
            'nama' => $depan.' '.$this->faker->lastName(),
            'nik' => DataPasienRealistis::nik('3273'.sprintf('%02d', $kecamatan), $lahir->toDateString(), $jk, $urut),
            'tanggal_lahir' => $lahir,
            'jenis_kelamin' => $jk,
            'no_hp' => '0812'.'0000'.$this->faker->numerify('####'),
            'alamat' => $this->faker->streetAddress().', Kota Bandung',
        ];
    }
}
