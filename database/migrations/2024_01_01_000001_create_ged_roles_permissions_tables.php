<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GED - Gestion Électronique de Documents
 * Migration: Roles and Permissions Tables
 * 
 * Conformité: GMP Annex 11, 21 CFR Part 11, ISO 13485
 * Objectif: Gestion granulaire des accès par rôle et privilège
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table des rôles - Définition des profils utilisateurs GMP
        Schema::create('ged_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // QA_Manager, QC_Analyst, Regulatory_Affairs, Standard_User
            $table->string('display_name', 150);
            $table->text('description')->nullable();
            $table->enum('access_level', ['read', 'write', 'review', 'approve', 'admin'])->default('read');
            $table->boolean('can_approve_documents')->default(false);
            $table->boolean('can_sign_electronically')->default(false);
            $table->boolean('can_manage_workflows')->default(false);
            $table->boolean('can_view_audit_trail')->default(false);
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('is_system_role')->default(false); // Rôles système non supprimables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active', 'access_level']);
        });

        // Table des permissions - Granularité fine des actions autorisées
        Schema::create('ged_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // document.create, document.approve, workflow.manage
            $table->string('display_name', 150);
            $table->string('module', 50); // documents, workflows, audit, users, signatures
            $table->string('action', 50); // create, read, update, delete, approve, sign
            $table->text('description')->nullable();
            $table->boolean('requires_signature')->default(false); // Action nécessitant signature électronique
            $table->boolean('is_auditable')->default(true); // Action tracée dans audit trail
            $table->timestamps();
            
            $table->index(['module', 'action']);
        });

        // Table pivot Rôle-Permission
        Schema::create('ged_role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('ged_roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('ged_permissions')->onDelete('cascade');
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
        });

        // Table pivot User-Role avec traçabilité
        Schema::create('ged_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('ged_roles')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable(); // Rôles temporaires
            $table->string('assignment_reason', 500)->nullable(); // Justification pour audit
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['user_id', 'role_id']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ged_user_roles');
        Schema::dropIfExists('ged_role_permission');
        Schema::dropIfExists('ged_permissions');
        Schema::dropIfExists('ged_roles');
    }
};
