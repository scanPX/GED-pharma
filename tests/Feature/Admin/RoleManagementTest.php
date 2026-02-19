<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_roles_and_permissions()
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
        $admin->gedRoles()->attach($role); // Uses assignRole logic if via method, distinct here via relation

        // Fix: Use assignRole relation manually or ensure user has permission
        // Since Gate check uses $user->hasPermission, and hasPermission uses $user->gedRoles
        
        $response = $this->actingAs($admin)
                         ->getJson('/api/ged/admin/roles');

        $response->assertStatus(200)
                 ->assertJsonStructure(['roles', 'permissions']);
    }

    public function test_admin_can_update_role_permissions()
    {
        $admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'display_name' => 'Admin']);
        $perm = Permission::firstOrCreate(['name' => 'user.manage', 'display_name' => 'Manage Users', 'module' => 'users', 'action' => 'manage']);
        $adminRole->permissions()->attach($perm);
        $admin->assignRole($adminRole); 
        // Need to load relation? assignRole does load('roles')

        $targetRole = Role::create(['name' => 'editor', 'display_name' => 'Editor']);
        $newPerm = Permission::create(['name' => 'doc.edit', 'display_name' => 'Edit Docs', 'module' => 'docs', 'action' => 'edit']);

        $response = $this->actingAs($admin)
                         ->putJson("/api/ged/admin/roles/{$targetRole->id}", [
                             'permissions' => [$newPerm->name]
                         ]);

        $response->assertStatus(200);
        
        // Assert role has new permission
        $this->assertTrue($targetRole->refresh()->permissions->contains('name', $newPerm->name));

        // Assert audit log
        $this->assertDatabaseHas('ged_audit_logs', [
            'action' => 'role_updated',
            'action_category' => 'access',
            'user_id' => $admin->id
        ]);
    }
}
