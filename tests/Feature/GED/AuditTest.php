<?php

namespace Tests\Feature\GED;

use App\Models\User;
use App\Models\GED\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $qaManager;
    protected User $qaAnalyst;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->adminUser = User::where('email', 'admin@ged-pharma.local')->first();
        $this->qaManager = User::where('email', 'sophie.martin@ged-pharma.local')->first();
        $this->qaAnalyst = User::where('email', 'pierre.dubois@ged-pharma.local')->first();
    }

    /** @test */
    public function authenticated_user_can_view_audit_logs()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/audit');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_view_audit_statistics()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/audit/statistics');

        $response->assertStatus(200);
    }

    /** @test */
    public function only_admin_can_verify_integrity()
    {
        // Admin should be able to verify
        $adminResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/audit/verify-integrity');
        $adminResponse->assertStatus(200);

        // Non-admin users should get 403
        $qaManagerResponse = $this->actingAs($this->qaManager, 'sanctum')
            ->getJson('/api/ged/audit/verify-integrity');
        $qaManagerResponse->assertStatus(403);

        $qaAnalystResponse = $this->actingAs($this->qaAnalyst, 'sanctum')
            ->getJson('/api/ged/audit/verify-integrity');
        $qaAnalystResponse->assertStatus(403);
    }

    /** @test */
    public function audit_export_requires_date_parameters()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/audit/export');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function audit_export_works_with_valid_dates()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->get('/api/ged/audit/export?start_date=2024-01-01&end_date=2024-12-31&format=json');

        // Should be 200 or file download response
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }

    /** @test */
    public function audit_log_pagination_works()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/audit?page=1&per_page=10');

        $response->assertStatus(200);
    }

    /** @test */
    public function audit_log_filtering_works()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/audit?action=login&category=access');

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_audit()
    {
        $response = $this->getJson('/api/ged/audit');
        $response->assertStatus(401);

        $response = $this->getJson('/api/ged/audit/statistics');
        $response->assertStatus(401);
    }

    /** @test */
    public function audit_logs_are_created_on_login()
    {
        $this->postJson('/api/ged/auth/login', [
            'email' => 'admin@ged-pharma.local',
            'password' => 'Admin@GED2024!',
        ]);

        $this->assertDatabaseHas('ged_audit_logs', [
            'action' => 'login_success',
        ]);
    }
}
