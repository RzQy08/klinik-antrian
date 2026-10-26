<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PoliFactory extends Factory
{
    protected $model = \App\Models\Poli::class;

    public function definition(): array
    {
        return [
            'nama_poli' => 'Poli ' . $this->faker->unique()->word(),
            'kode_poli' => strtoupper($this->faker->unique()->lexify('?')),
            'aktif' => true,
        ];
    }
}
