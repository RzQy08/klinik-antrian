<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PasienFactory extends Factory
{
    protected $model = \App\Models\Pasien::class;

    public function definition(): array
    {
        return [
            'no_rm' => 'RM-' . $this->faker->unique()->numerify('######'),
            'nama' => $this->faker->name(),
            'nik' => $this->faker->unique()->numerify('################'),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-70 years', '-1 years'),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'no_hp' => '08' . $this->faker->numerify('##########'),
            'alamat' => $this->faker->address(),
        ];
    }
}
