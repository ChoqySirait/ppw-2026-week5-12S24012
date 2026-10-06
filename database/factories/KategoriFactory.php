<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriFactory extends Factory
{
    protected $model = Kategori::class;

    public function definition(): array
    {
        return [
            'kode_kategori' => strtoupper($this->faker->unique()->bothify('KAT-###')),
            'nama_kategori' => $this->faker->words(2, true),
        ];
    }
}
