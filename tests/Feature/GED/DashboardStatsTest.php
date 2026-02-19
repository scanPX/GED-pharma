<?php

namespace Tests\Feature\GED;

use App\Models\User;
use App\Models\GED\Document;
use App\Models\GED\DocumentCategory;
use App\Models\GED\DocumentType;
use App\Models\GED\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
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
    public function dashboard_api_returns_structured_data()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'stats' => [
                    'effective_documents',
                    'effective_change',
                    'pending_approval',
                    'my_pending_tasks',
                    'review_due',
                ],
                'recent_documents',
                'recent_activity',
                'compliance_alerts',
            ]
        ]);
    }
}
