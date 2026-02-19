<?php

namespace Tests\Feature\GED;

use App\Models\User;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->adminUser = User::where('email', 'admin@ged-pharma.local')->first();
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/ged/auth/login', [
            'email' => 'admin@ged-pharma.local',
            'password' => 'Admin@GED2024!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token',
                'expires_at',
            ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/ged/auth/login', [
            'email' => 'admin@ged-pharma.local',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function user_cannot_login_with_nonexistent_email()
    {
        $response = $this->postJson('/api/ged/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_user_can_get_profile()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                ]
            ]);
    }

    /** @test */
    public function authenticated_user_can_get_permissions()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/auth/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'permissions',
                'roles',
            ]);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        // Login first to get a real token
        $loginResponse = $this->postJson('/api/ged/auth/login', [
            'email' => 'admin@ged-pharma.local',
            'password' => 'Admin@GED2024!',
        ]);
        
        $token = $loginResponse->json('token');
        
        $response = $this->withToken($token)
            ->postJson('/api/ged/auth/logout');

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/ged/auth/me');
        $response->assertStatus(401);

        $response = $this->getJson('/api/ged/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/ged/documents');
        $response->assertStatus(401);
    }

    /** @test */
    public function login_validation_requires_email_and_password()
    {
        $response = $this->postJson('/api/ged/auth/login', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $response = $this->postJson('/api/ged/auth/login', [
            'email' => 'admin@ged-pharma.local',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $this->adminUser->update(['is_active' => false]);

        $response = $this->postJson('/api/ged/auth/login', [
            'email' => 'admin@ged-pharma.local',
            'password' => 'Admin@GED2024!',
        ]);

        $response->assertStatus(422);
    }
}
