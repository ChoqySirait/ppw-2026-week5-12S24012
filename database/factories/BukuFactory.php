<?php

namespace Database\Factories;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

class BukuFactory extends Factory
{
    protected $model = Buku::class;

    public function definition(): array
    {
        return [
            'isbn'         => $this->faker->unique()->isbn13(),
            'judul'        => rtrim($this->faker->sentence(4), '.'),
            'penulis'      => $this->faker->name(),
            'penerbit'     => $this->faker->company(),
            'tahun_terbit' => $this->faker->numberBetween(2015, 2026),
            'kategori_id'  => Kategori::factory(),
            'stok'         => $this->faker->numberBetween(1, 40),
            'sinopsis'     => $this->faker->paragraph(3),
        ];
    }
}
