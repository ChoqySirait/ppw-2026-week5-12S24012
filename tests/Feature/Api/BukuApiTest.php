<?php

namespace Tests\Feature\Api;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BukuApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Kategori $kategori1;

    protected Kategori $kategori2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->kategori1 = Kategori::create([
            'kode_kategori' => 'TIK',
            'nama_kategori' => 'Teknologi Informasi & Komputer',
        ]);

        $this->kategori2 = Kategori::create([
            'kode_kategori' => 'SAIN',
            'nama_kategori' => 'Sains & Matematika',
        ]);
    }

    /**
     * Uji GET /api/v1/bukus berhasil mengambil katalog dengan pagination dan HATEOAS.
     */
    public function test_get_bukus_index_returns_paginated_catalog(): void
    {
        Buku::create([
            'isbn' => '978-000-0001-01-0',
            'judul' => 'Laravel 11 Deep Dive',
            'penulis' => 'Taylor Otwell',
            'penerbit' => 'Laravel Press',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 5,
        ]);

        $response = $this->getJson('/api/v1/bukus');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'isbn',
                    'judul',
                    'penulis',
                    'penerbit',
                    'tahun_terbit',
                    'stok',
                    'kategori',
                    'peminjamans_count',
                    '_links' => [
                        'self',
                        'referensi_eksternal',
                        'pinjam_buku',
                    ],
                ],
            ],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            'links' => ['first', 'last'],
        ]);
    }

    /**
     * Uji pencarian katalog berdasarkan judul, penulis, dan ISBN.
     */
    public function test_get_bukus_search_filter(): void
    {
        Buku::create([
            'isbn' => '978-111-2222-33-3',
            'judul' => 'Pemrograman REST API',
            'penulis' => 'Bona Siregar',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 3,
        ]);

        Buku::create([
            'isbn' => '978-444-5555-66-6',
            'judul' => 'Kalkulus Lanjut',
            'penulis' => 'Dosen Sains',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2023,
            'kategori_id' => $this->kategori2->id,
            'stok' => 2,
        ]);

        // Cari berdasarkan judul
        $resJudul = $this->getJson('/api/v1/bukus?search=REST');
        $resJudul->assertStatus(200);
        $this->assertCount(1, $resJudul->json('data'));
        $this->assertEquals('Pemrograman REST API', $resJudul->json('data.0.judul'));

        // Cari berdasarkan penulis
        $resPenulis = $this->getJson('/api/v1/bukus?search=Bona');
        $resPenulis->assertStatus(200);
        $this->assertCount(1, $resPenulis->json('data'));

        // Cari berdasarkan ISBN
        $resIsbn = $this->getJson('/api/v1/bukus?search=444-5555');
        $resIsbn->assertStatus(200);
        $this->assertCount(1, $resIsbn->json('data'));
        $this->assertEquals('Kalkulus Lanjut', $resIsbn->json('data.0.judul'));
    }

    /**
     * Uji filter kategori dan filter ketersediaan stok (tersedia=1).
     */
    public function test_get_bukus_filter_kategori_and_tersedia(): void
    {
        Buku::create([
            'isbn' => '978-001-001',
            'judul' => 'Buku Habis',
            'penulis' => 'Penulis A',
            'penerbit' => 'Penerbit A',
            'tahun_terbit' => 2023,
            'kategori_id' => $this->kategori1->id,
            'stok' => 0,
        ]);

        Buku::create([
            'isbn' => '978-002-002',
            'judul' => 'Buku Ada',
            'penulis' => 'Penulis B',
            'penerbit' => 'Penerbit B',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 4,
        ]);

        // Filter kategori_id
        $resKategori = $this->getJson('/api/v1/bukus?kategori_id='.$this->kategori2->id);
        $resKategori->assertStatus(200);
        $this->assertCount(0, $resKategori->json('data'));

        // Filter tersedia=1 (hanya buku dengan stok > 0)
        $resTersedia = $this->getJson('/api/v1/bukus?tersedia=1');
        $resTersedia->assertStatus(200);
        $this->assertCount(1, $resTersedia->json('data'));
        $this->assertEquals('Buku Ada', $resTersedia->json('data.0.judul'));
    }

    /**
     * Uji pengurutan katalog berdasarkan whitelist kolom.
     */
    public function test_get_bukus_sorting(): void
    {
        Buku::create([
            'isbn' => '978-001',
            'judul' => 'A First Book',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2020,
            'kategori_id' => $this->kategori1->id,
            'stok' => 10,
        ]);

        Buku::create([
            'isbn' => '978-002',
            'judul' => 'Z Last Book',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 2,
        ]);

        $resAsc = $this->getJson('/api/v1/bukus?sort=judul&direction=asc');
        $this->assertEquals('A First Book', $resAsc->json('data.0.judul'));

        $resDesc = $this->getJson('/api/v1/bukus?sort=-judul');
        $this->assertEquals('Z Last Book', $resDesc->json('data.0.judul'));
    }

    /**
     * Uji GET /api/v1/bukus/{id} detail buku berhasil dan 404 jika tidak ditemukan.
     */
    public function test_get_buku_detail_and_404_not_found(): void
    {
        $buku = Buku::create([
            'isbn' => '978-777-8888-99-9',
            'judul' => 'Basis Data Lanjut',
            'penulis' => 'Tim Dosen',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2023,
            'kategori_id' => $this->kategori1->id,
            'stok' => 7,
        ]);

        $response = $this->getJson('/api/v1/bukus/'.$buku->id);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $buku->id,
                'judul' => 'Basis Data Lanjut',
            ],
        ]);

        // 404 RFC 7807
        $notFound = $this->getJson('/api/v1/bukus/999999');
        $notFound->assertStatus(404);
        $this->assertEquals('application/problem+json', $notFound->headers->get('Content-Type'));
        $notFound->assertJson([
            'title' => 'Not Found',
            'status' => 404,
        ]);
    }

    /**
     * Uji POST buku baru (201 Created, BukuResource, header Location).
     */
    public function test_post_buku_success_with_location_header(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $payload = [
            'isbn' => '978-623-0100-00-1',
            'judul' => 'Arsitektur Perangkat Lunak Modern',
            'penulis' => 'Martin Fowler',
            'penerbit' => 'Addison-Wesley',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 5,
            'sinopsis' => 'Buku komprehensif mengenai perancangan arsitektur backend.',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/bukus', $payload);

        $response->assertStatus(201);
        $newId = $response->json('data.id');
        $this->assertNotNull($newId);
        $response->assertHeader('Location', url('/api/v1/bukus/'.$newId));
        $this->assertDatabaseHas('bukus', ['isbn' => '978-623-0100-00-1']);
    }

    /**
     * Uji validasi ISBN duplikat pada POST buku (422 RFC 7807).
     */
    public function test_post_buku_fails_on_duplicate_isbn(): void
    {
        Buku::create([
            'isbn' => '978-623-DUPLIKAT',
            'judul' => 'Buku Asli',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 3,
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/bukus', [
                'isbn' => '978-623-DUPLIKAT',
                'judul' => 'Buku Duplikat',
                'penulis' => 'Penulis Lain',
                'penerbit' => 'Penerbit Lain',
                'tahun_terbit' => 2024,
                'kategori_id' => $this->kategori1->id,
                'stok' => 1,
            ]);

        $response->assertStatus(422);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJsonValidationErrors(['isbn']);
    }

    /**
     * Uji PUT vs PATCH: PUT mewajibkan seluruh field, PATCH mengizinkan parsial.
     */
    public function test_put_requires_full_representation_while_patch_allows_partial(): void
    {
        $buku = Buku::create([
            'isbn' => '978-000-PUT-PATCH',
            'judul' => 'Judul Awal',
            'penulis' => 'Penulis Awal',
            'penerbit' => 'Penerbit Awal',
            'tahun_terbit' => 2022,
            'kategori_id' => $this->kategori1->id,
            'stok' => 10,
            'sinopsis' => 'Sinopsis Awal',
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        // 1. PUT parsial harus DITOLAK (422) karena PUT adalah penggantian penuh
        $resPutIncomplete = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/bukus/'.$buku->id, [
                'judul' => 'Judul Baru Saja',
            ]);
        $resPutIncomplete->assertStatus(422);
        $resPutIncomplete->assertJsonValidationErrors(['isbn', 'penulis', 'penerbit', 'tahun_terbit', 'kategori_id', 'stok']);

        // 2. PATCH parsial harus BERHASIL dan hanya mengubah field yang dikirim
        $resPatch = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/bukus/'.$buku->id, [
                'judul' => 'Judul Baru via PATCH',
                'stok' => 15,
            ]);
        $resPatch->assertStatus(200);
        $resPatch->assertJson([
            'data' => [
                'judul' => 'Judul Baru via PATCH',
                'stok' => 15,
                'penulis' => 'Penulis Awal', // Field lain tidak berubah
                'penerbit' => 'Penerbit Awal',
            ],
        ]);

        // 3. PUT lengkap harus BERHASIL menggantikan seluruh field
        $resPutComplete = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/bukus/'.$buku->id, [
                'isbn' => '978-000-PUT-PATCH',
                'judul' => 'Judul Lengkap via PUT',
                'penulis' => 'Penulis Baru PUT',
                'penerbit' => 'Penerbit Baru PUT',
                'tahun_terbit' => 2024,
                'kategori_id' => $this->kategori2->id,
                'stok' => 20,
                'sinopsis' => 'Sinopsis Baru PUT',
            ]);
        $resPutComplete->assertStatus(200);
        $resPutComplete->assertJson([
            'data' => [
                'judul' => 'Judul Lengkap via PUT',
                'penulis' => 'Penulis Baru PUT',
                'stok' => 20,
            ],
        ]);
    }

    /**
     * Uji DELETE buku berhasil (204 No Content).
     */
    public function test_delete_buku_success_with_204_no_content(): void
    {
        $buku = Buku::create([
            'isbn' => '978-DEL-001',
            'judul' => 'Buku Siap Dihapus',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 3,
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/bukus/'.$buku->id);

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        $this->assertDatabaseMissing('bukus', ['id' => $buku->id]);
    }

    /**
     * Uji DELETE buku ditolak (409 Conflict) apabila ada peminjaman aktif ('dipinjam').
     */
    public function test_delete_buku_fails_when_has_active_loan(): void
    {
        $buku = Buku::create([
            'isbn' => '978-DEL-ACTIVE',
            'judul' => 'Buku Sedang Dipinjam',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 1,
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PJ-TEST-ACTIVE',
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24012',
            'nama_peminjam' => 'Mahasiswa Test',
            'tanggal_pinjam' => '2026-11-01',
            'tanggal_jatuh_tempo' => '2026-11-08',
            'status' => 'dipinjam',
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/bukus/'.$buku->id);

        $response->assertStatus(409);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Conflict',
            'status' => 409,
        ]);
        $this->assertDatabaseHas('bukus', ['id' => $buku->id]);
    }

    /**
     * Uji HATEOAS dinamis: tautan pinjam_buku hanya muncul saat stok > 0.
     */
    public function test_hateoas_borrow_link_appears_dynamically_based_on_stock(): void
    {
        $bukuAdaStok = Buku::create([
            'isbn' => '978-HATEOAS-1',
            'judul' => 'Buku Ada Stok',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 3,
        ]);

        $bukuStokNol = Buku::create([
            'isbn' => '978-HATEOAS-0',
            'judul' => 'Buku Stok Habis',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori1->id,
            'stok' => 0,
        ]);

        // Buku dengan stok > 0 harus memiliki tautan pinjam_buku
        $resAda = $this->getJson('/api/v1/bukus/'.$bukuAdaStok->id);
        $resAda->assertStatus(200);
        $linksAda = $resAda->json('data._links');
        $this->assertArrayHasKey('pinjam_buku', $linksAda);
        $this->assertEquals('POST', $linksAda['pinjam_buku']['method']);

        // Buku dengan stok 0 TIDAK boleh memiliki tautan pinjam_buku
        $resNol = $this->getJson('/api/v1/bukus/'.$bukuStokNol->id);
        $resNol->assertStatus(200);
        $linksNol = $resNol->json('data._links');
        $this->assertArrayNotHasKey('pinjam_buku', $linksNol);
    }
}
