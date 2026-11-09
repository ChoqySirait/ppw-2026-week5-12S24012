<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji login berhasil dengan kredensial yang valid menghasilkan Bearer token.
     */
    public function test_login_success_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'pustakawan@del.ac.id',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ],
        ]);
        $response->assertJson([
            'success' => true,
            'data' => [
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => 'pustakawan@del.ac.id',
                ],
            ],
        ]);
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Uji login gagal saat password salah (HTTP 401 RFC 7807).
     */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'pustakawan@del.ac.id',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'password-salah',
        ]);

        $response->assertStatus(401);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Unauthorized',
            'status' => 401,
        ]);
    }

    /**
     * Uji login validasi gagal saat input kosong (HTTP 422 RFC 7807).
     */
    public function test_login_validation_fails_on_empty_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Uji endpoint /me tanpa token ditolak (HTTP 401 RFC 7807).
     */
    public function test_me_fails_without_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Unauthorized',
            'status' => 401,
        ]);
    }

    /**
     * Uji endpoint /me dengan token tidak valid ditolak (HTTP 401 RFC 7807).
     */
    public function test_me_fails_with_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-sample')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'title' => 'Unauthorized',
            'status' => 401,
        ]);
    }

    /**
     * Uji endpoint /me berhasil dengan Bearer token yang sah.
     */
    public function test_me_success_with_valid_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Bona Siregar',
            'email' => 'bona@del.ac.id',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => 'Bona Siregar',
                'email' => 'bona@del.ac.id',
            ],
        ]);
        // Pastikan tidak ada password di payload
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    /**
     * Uji logout mencabut token aktif secara stateless.
     */
    public function test_logout_revokes_token_successfully(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Pastikan token terhapus dari database
        $this->assertCount(0, $user->fresh()->tokens);

        // Reset state guard untuk mensimulasikan request HTTP baru secara terisolasi
        $this->app['auth']->forgetGuards();

        // Verifikasi token telah dicabut dan tidak dapat digunakan kembali
        $subsequentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $subsequentResponse->assertStatus(401);
    }
}
