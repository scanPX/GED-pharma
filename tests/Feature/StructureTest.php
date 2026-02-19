<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GED\Entity;
use App\Models\GED\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');

        // Create Admin User First (so ID 1 exists)
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@ged-pharma.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Seed Roles directly
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrateur',
            'description' => 'System Admin',
            'access_level' => 'admin',
            'is_active' => true
        ]);
        Role::create([
            'name' => 'standard_user',
            'display_name' => 'Utilisateur Standard',
            'description' => 'Standard User',
            'access_level' => 'read',
            'is_active' => true
        ]);

        // Initialize Permissions
        if (class_exists(\App\Models\GED\Permission::class)) {
             $perm = \App\Models\GED\Permission::create([
                 'name' => 'user.manage',
                 'display_name' => 'Manage Users',
                 'module' => 'users',
                 'action' => 'manage',
                 'description' => 'Can create and edit users'
             ]);
             
             // Attach permission to role
             $adminRole->permissions()->attach($perm->id, [
                 'granted_by' => $admin->id,
             ]);
        }

        // Run StructureSeeder
        $this->seed(\Database\Seeders\StructureSeeder::class);
        
        // Assign Role to Admin
        $admin->assignRole($adminRole, $admin->id); // Self-assigned
    }

    public function test_admin_can_fetch_entities()
    {
        $admin = User::where('email', 'admin@ged-pharma.local')->first();
        
        $response = $this->actingAs($admin)->getJson('/api/ged/admin/entities');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'name', 'description']
                 ]);
    }

    public function test_admin_can_fetch_departments_for_entity()
    {
        $admin = User::where('email', 'admin@ged-pharma.local')->first();
        $entity = Entity::first();
        
        $response = $this->actingAs($admin)->getJson("/api/ged/admin/entities/{$entity->id}/departments");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'name', 'entitie_id']
                 ]);
    }

    public function test_admin_can_create_user_with_structure()
    {
        $admin = User::where('email', 'admin@ged-pharma.local')->first();
        $entity = Entity::with('departements.fonctions')->first();
        $dept = $entity->departements->first();
        $func = $dept->fonctions->first();
        
        $role = Role::where('name', 'standard_user')->first();

        // Ensure we send valid IDs
        $this->assertNotNull($dept, 'Department mismatch in seeder');
        $this->assertNotNull($func, 'Function mismatch in seeder');

        $userData = [
            'name' => 'Test Hierarchy User',
            'email' => 'hierarchy.test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'standard_user', // Request expects string name 'exists:ged_roles,name'
            'department_id' => $dept->id,
            'fonction_id' => $func->id,
            'title' => 'Test Title',
            'must_change_password' => true,
        ];

        $response = $this->actingAs($admin)->postJson('/api/ged/admin/users', $userData);
        
        // Debug if fails
        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201)
                 ->assertJsonPath('data.email', 'hierarchy.test@example.com')
                 ->assertJsonPath('data.department_id', $dept->id)
                 ->assertJsonPath('data.fonction_id', $func->id);
    }
}
