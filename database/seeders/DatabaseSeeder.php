<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed GED reference data (departments, permissions, roles, statuses, etc.)
        $this->call([
            GedSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
