<?php

namespace Database\Factories;

use App\Models\Poli;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokterFactory extends Factory
{
    protected $model = \App\Models\Dokter::class;

    public function definition(): array
    {
        return [
            'poli_id' => Poli::factory(),
            'nama' => 'dr. ' . $this->faker->name(),
            'no_sip' => 'SIP-' . $this->faker->unique()->numerify('######'),
            'aktif' => true,
        ];
    }
}
