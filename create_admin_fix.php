<?php

use App\Models\User;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Ensure we have an admin role
$role = Role::firstOrCreate(
    ['name' => 'admin'],
    ['display_name' => 'Administrator', 'description' => 'System Administrator']
);

// Ensure we have the permission
$perm = Permission::firstOrCreate(
    ['name' => 'user.manage'],
    ['display_name' => 'Manage Users', 'module' => 'users', 'action' => 'manage']
);

// Link permission to role
if (!$role->permissions->contains($perm->id)) {
    $role->permissions()->attach($perm->id, ['granted_by' => 1]); // Assuming ID 1 or system
}

// Create Admin User if not exists
$user = User::firstOrCreate(
    ['email' => 'admin@ged.com'],
    [
        'name' => 'System Admin',
        'password' => Hash::make('password'),
        'department' => 'IT',
        'title' => 'Administrator',
        'is_active' => true,
        'must_change_password' => false
    ]
);

// Assign role
if (!$user->hasRole('admin')) {
    // We use attach directly to avoid 'assigned_by' constraint if user table is empty of other users
    // If assigned_by is nullable, we can pass null. If not, we use the user's own ID if it exists?
    // Migration said: $table->foreignId('assigned_by')->nullable()->constrained('users');
    // So null is fine.
    
    $user->roles()->attach($role->id, [
        'assigned_by' => $user->id, // Assign to self? or null
        'assigned_at' => now(),
        'is_active' => true
    ]);
}

echo "Admin user created or validated.\n";
echo "Email: admin@ged.com\n";
echo "Password: password\n";
