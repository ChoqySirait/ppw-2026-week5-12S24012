<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftarkan rute uji untuk skenario error API
        Route::prefix('api/v1/test')->group(function () {
            Route::get('protected', fn () => response()->json(['secret' => 'ok']))->middleware('auth:sanctum');
            Route::get('conflict', fn () => abort(409, 'Konflik transaksi terdeteksi.'));
            Route::post('validation', function (Request $request) {
                $request->validate(['field_wajib' => 'required|string']);

                return response()->json(['status' => 'ok']);
            });
            Route::get('server-error', fn () => throw new \RuntimeException('Kesalahan internal tidak terduga.'));
        });
    }

    /**
     * Uji 404 Not Found menghasilkan format RFC 7807 dan Content-Type application/problem+json.
     */
    public function test_api_404_returns_rfc7807_problem_details(): void
    {
        $response = $this->get('/api/v1/route-yang-tidak-ada');

        $response->assertStatus(404);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
        ]);
        $response->assertJson([
            'title' => 'Not Found',
            'status' => 404,
        ]);
    }

    /**
     * Uji 401 Unauthorized menghasilkan format RFC 7807.
     */
    public function test_api_401_returns_rfc7807_problem_details(): void
    {
        $response = $this->get('/api/v1/test/protected');

        $response->assertStatus(401);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
        ]);
        $response->assertJson([
            'title' => 'Unauthorized',
            'status' => 401,
        ]);
    }

    /**
     * Uji 422 Unprocessable Content menghasilkan format RFC 7807 beserta atribut errors.
     */
    public function test_api_422_returns_rfc7807_problem_details_with_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/test/validation', []);

        $response->assertStatus(422);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
            'errors' => ['field_wajib'],
        ]);
        $response->assertJson([
            'title' => 'Unprocessable Content',
            'status' => 422,
        ]);
    }

    /**
     * Uji 409 Conflict menghasilkan format RFC 7807.
     */
    public function test_api_409_returns_rfc7807_problem_details(): void
    {
        $response = $this->get('/api/v1/test/conflict');

        $response->assertStatus(409);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
        ]);
        $response->assertJson([
            'title' => 'Conflict',
            'status' => 409,
            'detail' => 'Konflik transaksi terdeteksi.',
        ]);
    }

    /**
     * Uji 500 Internal Server Error menghasilkan format RFC 7807.
     */
    public function test_api_500_returns_rfc7807_problem_details(): void
    {
        $response = $this->get('/api/v1/test/server-error');

        $response->assertStatus(500);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
        ]);
        $response->assertJson([
            'title' => 'Internal Server Error',
            'status' => 500,
        ]);
    }

    /**
     * Memastikan request web non-API tidak diubah menjadi application/problem+json.
     */
    public function test_web_route_error_retains_standard_html_behavior(): void
    {
        $response = $this->get('/halaman-web-yang-pasti-tidak-ada-404');

        $response->assertStatus(404);
        $this->assertNotEquals('application/problem+json', $response->headers->get('Content-Type'));
    }
}
