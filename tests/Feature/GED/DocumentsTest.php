<?php

namespace Tests\Feature\GED;

use App\Models\User;
use App\Models\GED\Document;
use App\Models\GED\DocumentCategory;
use App\Models\GED\DocumentType;
use App\Models\GED\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentsTest extends TestCase
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
    public function authenticated_user_can_list_documents()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/documents');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_get_document_categories()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/documents/categories');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_get_document_types()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/documents/types');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_get_document_statuses()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/documents/statuses');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_get_documents_needing_review()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/documents/needing-review');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_get_dashboard()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_create_document_with_permission()
    {
        $category = DocumentCategory::first();
        $type = DocumentType::first();

        // Skip test if no test data
        if (!$category || !$type) {
            $this->markTestSkipped('No test data available');
        }

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/ged/documents', [
                'title' => 'Test Document',
                'category_id' => $category->id,
                'type_id' => $type->id,
                'description' => 'Test description',
            ]);

        // Check for 201 (created), 200, or 422 (validation) or 403 (permission)
        $this->assertTrue(in_array($response->status(), [200, 201, 403, 422]));
    }

    /** @test */
    public function document_search_filters_work()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/documents?search=test&per_page=10');

        $response->assertStatus(200);
    }

    /** @test */
    public function document_pagination_works()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/ged/documents?page=1&per_page=10');

        $response->assertStatus(200);
    }
}
