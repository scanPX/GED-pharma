<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$user = User::where('email', 'admin@ged.com')->first();

if (!$user) {
    echo "User NOT FOUND\n";
    exit(1);
}

$user->load('roles.permissions');

echo "User: {$user->name} ({$user->email})\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";

$perms = $user->roles->flatMap(fn($r) => $r->permissions)->pluck('name')->unique()->values();
echo "Permissions: " . $perms->join(', ') . "\n";

echo "hasPermission('user.manage'): " . ($user->hasPermission('user.manage') ? 'YES' : 'NO') . "\n";
echo "can('user.manage'): " . ($user->can('user.manage') ? 'YES' : 'NO') . "\n";
