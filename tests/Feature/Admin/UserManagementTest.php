<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use App\Models\GED\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming roles seeded or available
    }

    public function test_admin_can_list_users()
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate([
            'name' => 'admin', 
            'display_name' => 'Administrator',
            'description' => 'Admin'
        ]);
        $perm = Permission::firstOrCreate([
            'name' => 'user.manage',
            'display_name' => 'Manage Users',
            'module' => 'users',
            'action' => 'manage'
        ]);
        $role->permissions()->attach($perm);
        $admin->gedRoles()->attach($role);

        $response = $this->actingAs($admin)
                         ->getJson('/api/ged/admin/users');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['data']]);
    }

    public function test_non_admin_cannot_list_users()
    {
        $user = User::factory()->create();
        // No role attached

        $response = $this->actingAs($user)
                         ->getJson('/api/ged/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user_with_audit_log()
    {
        $admin = User::factory()->create();
        $roleAdmin = Role::firstOrCreate([
            'name' => 'admin', 
            'display_name' => 'Administrator',
            'description' => 'Admin'
        ]);
        $perm = Permission::firstOrCreate([
            'name' => 'user.manage',
            'display_name' => 'Manage Users',
            'module' => 'users',
            'action' => 'manage'
        ]);
        $roleAdmin->permissions()->attach($perm);
        $admin->gedRoles()->attach($roleAdmin);

        $roleUser = Role::firstOrCreate([
            'name' => 'standard_user', 
            'display_name' => 'Standard User',
            'description' => 'User'
        ]);

        $userData = [
            'name' => 'Test Employee',
            'email' => 'test.employee@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'standard_user',
            'department' => 'QC',
            'title' => 'Analyst'
        ];

        $response = $this->actingAs($admin)
                         ->postJson('/api/ged/admin/users', $userData);

        if ($response->status() !== 201) {
            dd($response->json());
        }
        $response->assertStatus(201)
                 ->assertJsonPath('data.email', 'test.employee@example.com');

        $this->assertDatabaseHas('users', ['email' => 'test.employee@example.com']);
        
        // Check Audit Log
        $this->assertDatabaseHas('ged_audit_logs', [
            'action' => 'user_created',
            'user_id' => $admin->id,
            'action_category' => 'access'
        ]);
    }

    public function test_admin_can_disable_user()
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate([
            'name' => 'admin', 
            'display_name' => 'Administrator',
            'description' => 'Admin'
        ]);
        $perm = Permission::firstOrCreate([
            'name' => 'user.manage',
            'display_name' => 'Manage Users',
            'module' => 'users',
            'action' => 'manage'
        ]);
        $role->permissions()->attach($perm);
        $admin->gedRoles()->attach($role);

        $targetUser = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)
                         ->patchJson("/api/ged/admin/users/{$targetUser->id}/toggle-active", [
                             'is_active' => false
                         ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'is_active' => 0
        ]);

        // Check Audit Log
        $this->assertDatabaseHas('ged_audit_logs', [
            'action' => 'user_disabled',
            'user_id' => $admin->id
        ]);
    }
}
