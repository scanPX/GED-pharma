<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('users');
print_r($columns);

$user = new \App\Models\User();
echo "Fillable: " . implode(', ', $user->getFillable()) . "\n";
