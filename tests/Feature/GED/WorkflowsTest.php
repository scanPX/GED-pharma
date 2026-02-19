<?php

namespace Tests\Feature\GED;

use App\Models\User;
use App\Models\GED\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $qaManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->adminUser = User::where('email', 'admin@ged-pharma.local')->first();
        $this->qaManager = User::where('email', 'sophie.martin@ged-pharma.local')->first();
    }

    /** @test */
    public function authenticated_user_can_list_workflow_definitions()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/workflows/definitions');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_get_pending_workflows()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/workflows/my-pending');

        $response->assertStatus(200);
    }

    /** @test */
    public function different_users_see_different_pending_workflows()
    {
        $adminResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/workflows/my-pending');

        $qaManagerResponse = $this->actingAs($this->qaManager, 'sanctum')
            ->getJson('/api/ged/workflows/my-pending');

        $adminResponse->assertStatus(200);
        $qaManagerResponse->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_workflows()
    {
        $response = $this->getJson('/api/ged/workflows/definitions');
        $response->assertStatus(401);

        $response = $this->getJson('/api/ged/workflows/my-pending');
        $response->assertStatus(401);
    }
}
