<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les champs GED à la table users
 * Conforme aux exigences 21 CFR Part 11 et GMP Annexe 11
 */
return new class extends Migration
{
    public function up(): void
    {
        // Créer la table des départements
        Schema::create('ged_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Ajouter les champs GED à users
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id', 50)->nullable()->after('name')->comment('Matricule employé');
            $table->string('title')->nullable()->after('employee_id')->comment('Titre/Fonction');
            $table->foreignId('department_id')->nullable()->after('title')
                  ->constrained('ged_departments')
                  ->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('department_id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('can_sign_electronically')->default(false)->after('is_active');
            $table->string('signature_pin')->nullable()->after('can_sign_electronically')
                  ->comment('PIN hashé pour signature électronique');
            $table->timestamp('last_login_at')->nullable()->after('signature_pin');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_ip');
            $table->integer('failed_login_attempts')->default(0)->after('password_changed_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('locked_at')->nullable()->after('locked_until');
            $table->string('timezone', 50)->default('Europe/Paris')->after('locked_at');
            $table->string('language', 5)->default('fr')->after('timezone');
            $table->timestamp('training_completed_at')->nullable()->after('language')
                  ->comment('Date de complétion de la formation système');
            
            // Index pour les recherches
            $table->index('employee_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'employee_id',
                'title',
                'department_id',
                'phone',
                'is_active',
                'can_sign_electronically',
                'signature_pin',
                'last_login_at',
                'last_login_ip',
                'password_changed_at',
                'failed_login_attempts',
                'locked_until',
                'locked_at',
                'timezone',
                'language',
                'training_completed_at',
            ]);
        });

        Schema::dropIfExists('ged_departments');
    }
};
