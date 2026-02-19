<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Entities
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Departements
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('entitie_id')->constrained('entities')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Fonctions
        Schema::create('fonctions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('departement_id')->constrained('departements')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Update Users (Add hierarchy columns)
        Schema::table('users', function (Blueprint $table) {
            // Drop old foreign key if exists (from 2024 migration)
            // We use a try-catch or safe approach because in some envs it might not exist if migrations changed.
            // But for RefreshDatabase it will exist.
            try {
                $table->dropForeign(['department_id']);
            } catch (\Exception $e) {
                // FK might not exist or driver issue
            }

            // Check if department_id exists
            if (!Schema::hasColumn('users', 'department_id')) {
                 $table->unsignedBigInteger('department_id')->nullable();
            }
            
            // Optional: Add new FK to new departements table
            // $table->foreign('department_id')->references('id')->on('departements')->nullOnDelete();

             // Add function_id
            if (!Schema::hasColumn('users', 'fonction_id')) {
                $table->foreignId('fonction_id')->nullable()->constrained('fonctions')->nullOnDelete();
            }
        });
        
        // Note: We'll need to manually ensure department_id in users refers to the new table if desired, 
        // or just rely on function->department->entity chain. 
        // However, user form likely wants to save all selected values. 
        // Given existing code uses `department_id`, we should link it to `departements` if possible.
        // If `department_id` already exists but wasn't a FK to `departements`, we might need to alter it.
        // For safety, let's leave existing `department_id` alone if it exists, but try to make it foreign key if compatible.
        // Attempting to add FK constraint to existing column might fail if data is incompatible.
        // We will assume `fonction_id` is the primary link for the new structure.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fonction_id')) {
                $table->dropForeign(['fonction_id']);
                $table->dropColumn('fonction_id');
            }
        });

        Schema::dropIfExists('fonctions');
        Schema::dropIfExists('departements');
        Schema::dropIfExists('entities');
    }
};
