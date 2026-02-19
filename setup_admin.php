<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== Admin Setup Script ===\n\n";

// 1. Create/Get Admin Role
$role = Role::firstOrCreate(
    ['name' => 'admin'],
    ['display_name' => 'Administrator', 'description' => 'System Administrator', 'is_active' => true]
);
echo "Role 'admin' ID: {$role->id}\n";

// 2. Create/Get user.manage Permission
$perm = Permission::firstOrCreate(
    ['name' => 'user.manage'],
    ['display_name' => 'Manage Users', 'module' => 'users', 'action' => 'manage']
);
echo "Permission 'user.manage' ID: {$perm->id}\n";

// 3. Link Permission to Role
$existingLink = DB::table('ged_role_permissions')
    ->where('role_id', $role->id)
    ->where('permission_id', $perm->id)
    ->first();

if (!$existingLink) {
    DB::table('ged_role_permissions')->insert([
        'role_id' => $role->id,
        'permission_id' => $perm->id,
        'granted_by' => null,
        'granted_at' => now(),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Linked permission to role.\n";
} else {
    echo "Permission already linked to role.\n";
}

// 4. Create Admin User
$user = User::firstOrCreate(
    ['email' => 'admin@ged.com'],
    [
        'name' => 'System Admin',
        'password' => Hash::make('password'),
        'is_active' => true,
        'must_change_password' => false
    ]
);
echo "User 'admin@ged.com' ID: {$user->id}\n";

// 5. Link Role to User
$existingUserRole = DB::table('ged_user_roles')
    ->where('user_id', $user->id)
    ->where('role_id', $role->id)
    ->first();

if (!$existingUserRole) {
    DB::table('ged_user_roles')->insert([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'assigned_by' => $user->id,
        'assigned_at' => now(),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Assigned 'admin' role to user.\n";
} else {
    echo "User already has 'admin' role.\n";
}

// 6. Verify
$user->load('roles.permissions');
echo "\n=== Verification ===\n";
echo "User Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "User Permissions (via hasPermission): " . ($user->hasPermission('user.manage') ? 'YES' : 'NO') . "\n";
echo "User can() check: " . ($user->can('user.manage') ? 'YES' : 'NO') . "\n";

echo "\n=== Complete ===\n";
echo "Login with: admin@ged.com / password\n";
