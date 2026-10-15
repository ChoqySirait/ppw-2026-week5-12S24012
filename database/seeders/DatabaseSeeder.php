<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Pustakawan Uji (Sesuai Spesifikasi Modul)
        User::firstOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            [
                'name' => 'Pustakawan Del',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Kategori Koleksi Del
        $kategoriList = [
            ['kode_kategori' => 'KAT-RPL', 'nama_kategori' => 'Rekayasa Perangkat Lunak'],
            ['kode_kategori' => 'KAT-SI',  'nama_kategori' => 'Sistem Informasi & Bisnis'],
            ['kode_kategori' => 'KAT-DS',  'nama_kategori' => 'Sains Data & AI'],
            ['kode_kategori' => 'KAT-JK',  'nama_kategori' => 'Jaringan & Keamanan Siber'],
            ['kode_kategori' => 'KAT-TI',  'nama_kategori' => 'Teknologi Informasi Dasar'],
        ];

        $kategoriModels = [];
        foreach ($kategoriList as $kat) {
            $kategoriModels[] = Kategori::firstOrCreate(
                ['kode_kategori' => $kat['kode_kategori']],
                ['nama_kategori' => $kat['nama_kategori']]
            );
        }

        // 3. Buku Skenario Khusus (Uji Batas Stok = 0 dan Stok = 1)
        // Buku Stok = 0: Digunakan untuk membuktikan Rollback & Tolak Transaksi ACID
        $bukuStokNol = Buku::firstOrCreate(
            ['isbn' => '978-602-03-8800-1'],
            [
                'judul' => 'Arsitektur Komputasi Cloud Skala Enterprise',
                'penulis' => 'Dr. Arnaldo Sinaga',
                'penerbit' => 'Del Press',
                'tahun_terbit' => 2024,
                'kategori_id' => $kategoriModels[0]->id,
                'stok' => 0,
                'sinopsis' => 'Buku pegangan komprehensif mengenai perancangan arsitektur cloud terdistribusi dengan toleransi kesalahan tinggi.',
            ]
        );

        // Buku Stok = 1: Digunakan untuk membuktikan Row-Level Locking (Pessimistic Concurrency)
        $bukuStokSatu = Buku::firstOrCreate(
            ['isbn' => '978-602-03-8801-8'],
            [
                'judul' => 'Struktur Data dan Algoritma Lanjut dengan Go',
                'penulis' => 'Immanuel Alexander',
                'penerbit' => 'Informatika Del',
                'tahun_terbit' => 2025,
                'kategori_id' => $kategoriModels[0]->id,
                'stok' => 1,
                'sinopsis' => 'Pembahasan mendalam algoritma penelusuran graf, dynamic programming, dan optimasi konkurensi data.',
            ]
        );

        // Sampel Buku Tersedia Lainnya
        $bukuNormal1 = Buku::firstOrCreate(
            ['isbn' => '978-602-03-8802-5'],
            [
                'judul' => 'Analisis dan Perancangan Sistem Berorientasi Objek',
                'penulis' => 'Prof. S. Pardede',
                'penerbit' => 'Erlangga Akademik',
                'tahun_terbit' => 2023,
                'kategori_id' => $kategoriModels[1]->id,
                'stok' => 5,
                'sinopsis' => 'Konsep pemodelan UML, pemetaan kebutuhan bisnis pengguna, dan penerapan best-practices design patterns.',
            ]
        );

        $bukuNormal2 = Buku::firstOrCreate(
            ['isbn' => '978-602-03-8803-2'],
            [
                'judul' => 'Dasar Rekayasa Jaringan Komputer & Ethical Hacking',
                'penulis' => 'Bambang Subroto, M.T.',
                'penerbit' => 'Andi Offset',
                'tahun_terbit' => 2024,
                'kategori_id' => $kategoriModels[3]->id,
                'stok' => 7,
                'sinopsis' => 'Eksplorasi protokol TCP/IP, keamanan perimeter firewall, serta teknik pengujian celah keamanan penetrasi jaringan.',
            ]
        );

        $bukuNormal3 = Buku::firstOrCreate(
            ['isbn' => '978-602-03-8804-9'],
            [
                'judul' => 'Pembelajaran Mesin Terapan untuk Analisis Citra Medis',
                'penulis' => 'Dr. Marihot Tampubolon',
                'penerbit' => 'Del Press',
                'tahun_terbit' => 2025,
                'kategori_id' => $kategoriModels[2]->id,
                'stok' => 3,
                'sinopsis' => 'Penerapan convolutional neural networks untuk segmentasi otomatis foto rontgen dan pemindaian CT-Scan.',
            ]
        );

        // 4. Sampel Riwayat Peminjaman (Status Dipinjam & Dikembalikan)
        Peminjaman::firstOrCreate(
            ['kode_pinjam' => 'PJ-20261015-TEST1'],
            [
                'buku_id' => $bukuNormal1->id,
                'nim_peminjam' => '12S24001',
                'nama_peminjam' => 'Mutiara Situmorang',
                'tanggal_pinjam' => now()->subDays(5)->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays(2)->toDateString(),
                'status' => 'dipinjam',
            ]
        );

        Peminjaman::firstOrCreate(
            ['kode_pinjam' => 'PJ-20261015-TEST2'],
            [
                'buku_id' => $bukuNormal2->id,
                'nim_peminjam' => '12S24002',
                'nama_peminjam' => 'Josep Simanjuntak',
                'tanggal_pinjam' => now()->subDays(10)->toDateString(),
                'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
                'tanggal_kembali' => now()->subDays(2)->toDateString(),
                'status' => 'dikembalikan',
            ]
        );
    }
}