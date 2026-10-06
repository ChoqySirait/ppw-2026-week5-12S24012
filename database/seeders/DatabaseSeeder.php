<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seed data realistis untuk SIPUS-Del.
     */
    public function run(): void
    {
        // 5 Kategori Realistis Bidang Komputer & Informatika Kampus Del
        $kategoris = [
            ['kode_kategori' => 'KAT-RPL', 'nama_kategori' => 'Rekayasa Perangkat Lunak'],
            ['kode_kategori' => 'KAT-AI',  'nama_kategori' => 'Kecerdasan Buatan & Data Science'],
            ['kode_kategori' => 'KAT-NET', 'nama_kategori' => 'Jaringan Komputer & Cyber Security'],
            ['kode_kategori' => 'KAT-SI',  'nama_kategori' => 'Sistem Informasi & Manajemen TI'],
            ['kode_kategori' => 'KAT-DB',  'nama_kategori' => 'Basis Data & Arsitektur Cloud'],
        ];

        foreach ($kategoris as $dataKategori) {
            $kategori = Kategori::create($dataKategori);

            // Generate 4 buku realistis per kategori (Total: 20 buku)
            Buku::factory()->count(4)->create([
                'kategori_id' => $kategori->id,
            ]);
        }
    }
}
