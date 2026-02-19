<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::all();

echo "ID | Name | Email | Role | Active\n";
echo str_repeat("-", 60) . "\n";

foreach ($users as $user) {
    echo sprintf(
        "%d | %s | %s | %s | %s\n",
        $user->id,
        $user->name,
        $user->email,
        $user->roles->first()?->name ?? 'None',
        $user->is_active ? 'Yes' : 'No'
    );
}
