<?php

namespace Tests\Feature\Api;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenLibraryConsumerTest extends TestCase
{
    use RefreshDatabase;

    protected Kategori $kategori;

    protected Buku $buku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kategori = Kategori::create([
            'kode_kategori' => 'TIK',
            'nama_kategori' => 'Teknologi Informasi',
        ]);

        $this->buku = Buku::create([
            'isbn' => '978-0-13-235088-4',
            'judul' => 'Clean Code',
            'penulis' => 'Robert C. Martin',
            'penerbit' => 'Prentice Hall',
            'tahun_terbit' => 2008,
            'kategori_id' => $this->kategori->id,
            'stok' => 5,
        ]);
    }

    /**
     * Uji sukses integrasi OpenLibrary: metadata buku berhasil disinkronkan.
     */
    public function test_external_reference_successful_sync(): void
    {
        $cleanIsbn = '9780132350884';

        Http::fake([
            "https://openlibrary.org/isbn/{$cleanIsbn}.json" => Http::response([
                'title' => 'Clean Code',
                'subtitle' => 'A Handbook of Agile Software Craftsmanship',
                'number_of_pages' => 464,
                'publishers' => ['Prentice Hall PTR'],
                'publish_date' => 'August 1, 2008',
                'works' => [
                    ['key' => '/works/OL15175231W'],
                ],
                'covers' => [8259441],
            ], 200),
        ]);

        $response = $this->getJson("/api/v1/bukus/{$this->buku->id}/referensi-eksternal");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'buku_id' => $this->buku->id,
                'status_sinkronisasi' => 'synced',
                'metadata' => [
                    'judul' => 'Clean Code',
                    'jumlah_halaman' => 464,
                    'penerbit' => ['Prentice Hall PTR'],
                    'openlibrary_url' => "https://openlibrary.org/isbn/{$cleanIsbn}",
                ],
            ],
        ]);
    }

    /**
     * Uji penanganan saat ISBN tidak ditemukan di OpenLibrary (404 eksternal).
     */
    public function test_external_reference_not_found_on_openlibrary(): void
    {
        $cleanIsbn = '9780132350884';

        Http::fake([
            "https://openlibrary.org/isbn/{$cleanIsbn}.json" => Http::response(null, 404),
        ]);

        $response = $this->getJson("/api/v1/bukus/{$this->buku->id}/referensi-eksternal");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'status_sinkronisasi' => 'not_found',
                'metadata' => null,
            ],
        ]);
    }

    /**
     * Uji penanganan saat koneksi ke OpenLibrary mengalami timeout (HTTP 504 RFC 7807).
     */
    public function test_external_reference_timeout_handled_gracefully(): void
    {
        $cleanIsbn = '9780132350884';

        Http::fake([
            "https://openlibrary.org/isbn/{$cleanIsbn}.json" => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 8000 milliseconds');
            },
        ]);

        $response = $this->getJson("/api/v1/bukus/{$this->buku->id}/referensi-eksternal");

        $response->assertStatus(504);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Gateway Timeout',
            'status' => 504,
        ]);
        $this->assertStringContainsString('timeout', strtolower($response->json('detail')));
    }

    /**
     * Uji penanganan saat OpenLibrary mengalami gangguan upstream (HTTP 502 RFC 7807).
     */
    public function test_external_reference_upstream_server_error_handled_gracefully(): void
    {
        $cleanIsbn = '9780132350884';

        Http::fake([
            "https://openlibrary.org/isbn/{$cleanIsbn}.json" => Http::response([
                'error' => 'Internal server error at OpenLibrary',
            ], 500),
        ]);

        $response = $this->getJson("/api/v1/bukus/{$this->buku->id}/referensi-eksternal");

        $response->assertStatus(502);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Bad Gateway',
            'status' => 502,
        ]);
    }

    /**
     * Uji penanganan saat ID buku lokal tidak ditemukan (HTTP 404 RFC 7807).
     */
    public function test_external_reference_buku_local_not_found(): void
    {
        $response = $this->getJson('/api/v1/bukus/999999/referensi-eksternal');

        $response->assertStatus(404);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Not Found',
            'status' => 404,
        ]);
    }
}
