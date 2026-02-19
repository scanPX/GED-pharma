<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use App\Models\GED\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_settings_defaults()
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'display_name' => 'Admin']);
        $perm = Permission::firstOrCreate(['name' => 'user.manage', 'display_name' => 'Manage Users', 'module' => 'users', 'action' => 'manage']);
        $role->permissions()->attach($perm);
        $admin->assignRole($role);

        $response = $this->actingAs($admin)
                         ->getJson('/api/ged/admin/settings');

        $response->assertStatus(200)
                 ->assertJsonStructure(['settings']);

        // Check defaults created
        $this->assertDatabaseHas('ged_system_settings', ['key' => 'security.password_expiry_days']);
    }

    public function test_admin_can_update_settings()
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'display_name' => 'Admin']);
        $perm = Permission::firstOrCreate(['name' => 'user.manage', 'display_name' => 'Manage Users', 'module' => 'users', 'action' => 'manage']);
        $role->permissions()->attach($perm);
        $admin->assignRole($role);

        // First fetch to create defaults
        $this->actingAs($admin)->getJson('/api/ged/admin/settings');

        $updateData = [
            'settings' => [
                [
                    'key' => 'security.password_expiry_days',
                    'value' => '60'
                ]
            ]
        ];

        $response = $this->actingAs($admin)
                         ->postJson('/api/ged/admin/settings', $updateData);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('ged_system_settings', [
            'key' => 'security.password_expiry_days',
            'value' => '60'
        ]);

        // Audit Log
        $this->assertDatabaseHas('ged_audit_logs', [
            'action' => 'config_updated',
            'user_id' => $admin->id
        ]);
    }

    public function test_non_admin_cannot_access_settings()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                         ->getJson('/api/ged/admin/settings');

        $response->assertStatus(403);
    }
}
