<?php

namespace Tests\Feature\Api;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeminjamanApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected Kategori $kategori;

    protected Buku $buku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-loan')->plainTextToken;

        $this->kategori = Kategori::create([
            'kode_kategori' => 'TIK',
            'nama_kategori' => 'Teknologi Informasi',
        ]);

        $this->buku = Buku::create([
            'isbn' => '978-602-0001-99-9',
            'judul' => 'Sistem Terdistribusi',
            'penulis' => 'Penulis Del',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori->id,
            'stok' => 2,
        ]);
    }

    /**
     * Uji GET /api/v1/peminjamans dengan filter status dan NIM.
     */
    public function test_get_peminjamans_with_filters(): void
    {
        Peminjaman::create([
            'kode_pinjam' => 'PJ-FILTER-1',
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24001',
            'nama_peminjam' => 'Mahasiswa 1',
            'tanggal_pinjam' => '2026-11-01',
            'tanggal_jatuh_tempo' => '2026-11-08',
            'status' => 'dipinjam',
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PJ-FILTER-2',
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24002',
            'nama_peminjam' => 'Mahasiswa 2',
            'tanggal_pinjam' => '2026-11-01',
            'tanggal_jatuh_tempo' => '2026-11-08',
            'tanggal_kembali' => '2026-11-05',
            'status' => 'dikembalikan',
        ]);

        // Filter status=dipinjam
        $resDipinjam = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/peminjamans?status=dipinjam');
        $resDipinjam->assertStatus(200);
        $this->assertCount(1, $resDipinjam->json('data'));
        $this->assertEquals('PJ-FILTER-1', $resDipinjam->json('data.0.kode_pinjam'));

        // Filter NIM
        $resNim = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/peminjamans?nim=12S24002');
        $resNim->assertStatus(200);
        $this->assertCount(1, $resNim->json('data'));
        $this->assertEquals('12S24002', $resNim->json('data.0.nim_peminjam'));
    }

    /**
     * Uji peminjaman buku berhasil (201 Created, stok berkurang tepat satu unit).
     */
    public function test_post_peminjaman_success_decrements_stock_by_one(): void
    {
        $initialStock = $this->buku->stok;

        $payload = [
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24012',
            'nama_peminjam' => 'Bona Siregar',
            'durasi_hari' => 7,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/peminjamans', $payload);

        $response->assertStatus(201);
        $newId = $response->json('data.id');
        $this->assertNotNull($newId);
        $response->assertHeader('Location', url('/api/v1/peminjamans/'.$newId));

        // Verifikasi stok berkurang tepat 1
        $this->assertEquals($initialStock - 1, $this->buku->fresh()->stok);

        // Verifikasi database record
        $this->assertDatabaseHas('peminjamans', [
            'id' => $newId,
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24012',
            'status' => 'dipinjam',
        ]);
    }

    /**
     * Uji peminjaman gagal saat stok habis (HTTP 422 RFC 7807).
     */
    public function test_post_peminjaman_fails_when_out_of_stock(): void
    {
        $this->buku->update(['stok' => 0]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/peminjamans', [
                'buku_id' => $this->buku->id,
                'nim_peminjam' => '12S24012',
                'nama_peminjam' => 'Bona Siregar',
                'durasi_hari' => 7,
            ]);

        $response->assertStatus(422);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Unprocessable Content',
            'status' => 422,
        ]);
        $this->assertStringContainsString('telah habis', $response->json('detail'));
    }

    /**
     * Uji peminjaman ganda dengan buku sama oleh NIM yang sama ditolak (HTTP 409 RFC 7807).
     */
    public function test_post_peminjaman_fails_on_duplicate_active_loan(): void
    {
        Peminjaman::create([
            'kode_pinjam' => 'PJ-EXISTING',
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24012',
            'nama_peminjam' => 'Bona Siregar',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/peminjamans', [
                'buku_id' => $this->buku->id,
                'nim_peminjam' => '12S24012',
                'nama_peminjam' => 'Bona Siregar',
                'durasi_hari' => 7,
            ]);

        $response->assertStatus(409);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Conflict',
            'status' => 409,
        ]);
        $this->assertStringContainsString('masih meminjam buku ini', $response->json('detail'));
    }

    /**
     * Uji pengembalian buku berhasil (200 OK, memulihkan kuota stok tepat satu unit).
     */
    public function test_patch_pengembalian_success_increments_stock_by_one(): void
    {
        $initialStock = $this->buku->stok;

        $peminjaman = Peminjaman::create([
            'kode_pinjam' => 'PJ-RETURN-1',
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24012',
            'nama_peminjam' => 'Bona Siregar',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/peminjamans/'.$peminjaman->id.'/kembalikan');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $peminjaman->id,
                'status' => 'dikembalikan',
            ],
        ]);
        $this->assertNotNull($response->json('data.tanggal_kembali'));

        // Verifikasi stok dipulihkan tepat satu
        $this->assertEquals($initialStock + 1, $this->buku->fresh()->stok);
    }

    /**
     * Uji idempotensi: pengembalian kedua ditolak dengan HTTP 409 Conflict dan stok tidak bertambah dua kali.
     */
    public function test_patch_pengembalian_fails_when_already_returned(): void
    {
        $peminjaman = Peminjaman::create([
            'kode_pinjam' => 'PJ-RETURN-DUPLICATE',
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24012',
            'nama_peminjam' => 'Bona Siregar',
            'tanggal_pinjam' => '2026-11-01',
            'tanggal_jatuh_tempo' => '2026-11-08',
            'tanggal_kembali' => '2026-11-05',
            'status' => 'dikembalikan',
        ]);

        $stockBefore = $this->buku->stok;

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/peminjamans/'.$peminjaman->id.'/kembalikan');

        $response->assertStatus(409);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Conflict',
            'status' => 409,
        ]);

        // Pastikan stok tidak bertambah
        $this->assertEquals($stockBefore, $this->buku->fresh()->stok);
    }

    /**
     * Uji HATEOAS pada PeminjamanResource: kembalikan_buku hanya tampil saat dipinjam.
     */
    public function test_hateoas_kembalikan_link_appears_dynamically_based_on_status(): void
    {
        $peminjamanAktif = Peminjaman::create([
            'kode_pinjam' => 'PJ-HATEOAS-ACTIVE',
            'buku_id' => $this->buku->id,
            'nim_peminjam' => '12S24012',
            'nama_peminjam' => 'Bona Siregar',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        // Status 'dipinjam': link kembalikan_buku harus ADA
        $resAktif = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/peminjamans/'.$peminjamanAktif->id);
        $resAktif->assertStatus(200);
        $linksAktif = $resAktif->json('data._links');
        $this->assertArrayHasKey('kembalikan_buku', $linksAktif);
        $this->assertEquals('PATCH', $linksAktif['kembalikan_buku']['method']);

        // Jalankan pengembalian
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/peminjamans/'.$peminjamanAktif->id.'/kembalikan');

        // Status 'dikembalikan': link kembalikan_buku harus HILANG
        $resKembali = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/peminjamans/'.$peminjamanAktif->id);
        $resKembali->assertStatus(200);
        $linksKembali = $resKembali->json('data._links');
        $this->assertArrayNotHasKey('kembalikan_buku', $linksKembali);
    }
}
