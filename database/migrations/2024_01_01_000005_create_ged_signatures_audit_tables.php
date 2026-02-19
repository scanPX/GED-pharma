<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GED - Gestion Électronique de Documents
 * Migration: Signatures Électroniques et Audit Trail
 * 
 * Conformité: 21 CFR Part 11, EU Annex 11, eIDAS
 * Objectif: Signature électronique conforme et traçabilité complète
 */
return new class extends Migration
{
    public function up(): void
    {
        // Signatures électroniques - Conformité 21 CFR Part 11
        Schema::create('ged_electronic_signatures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            
            // Document signé
            $table->foreignId('document_id')->nullable()->constrained('ged_documents');
            $table->foreignId('document_version_id')->nullable()->constrained('ged_document_versions');
            
            // Contexte de signature
            $table->string('signable_type', 100); // Model class (polymorphic)
            $table->unsignedBigInteger('signable_id');
            
            // Signification de la signature
            $table->enum('meaning', [
                'created',      // Création
                'reviewed',     // Revue
                'verified',     // Vérification
                'approved',     // Approbation
                'authorized',   // Autorisation
                'released',     // Libération
                'acknowledged', // Prise de connaissance
                'witnessed'     // Témoin
            ]);
            $table->string('meaning_description', 500)->nullable();
            
            // Authentification - 21 CFR Part 11 §11.200
            $table->string('authentication_method', 50); // password, 2fa, biometric, certificate
            $table->boolean('identity_verified')->default(false);
            $table->timestamp('authenticated_at')->useCurrent();
            
            // Données de signature
            $table->text('signature_data'); // Données chiffrées
            $table->string('signature_hash', 64); // SHA-256 de la signature
            $table->string('document_hash', 64); // Hash du document au moment de la signature
            
            // Non-répudiation
            $table->string('user_full_name', 255); // Nom figé au moment de signature
            $table->string('user_title', 100)->nullable(); // Titre/fonction
            $table->string('user_department', 100)->nullable();
            
            // Horodatage sécurisé
            $table->timestamp('signed_at')->useCurrent();
            $table->string('timestamp_token', 500)->nullable(); // RFC 3161 timestamp
            $table->boolean('timestamp_verified')->default(false);
            
            // Contexte technique (audit)
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->string('session_id', 100)->nullable();
            $table->json('device_info')->nullable();
            
            // Validité
            $table->boolean('is_valid')->default(true);
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 500)->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            
            // Raison et commentaire
            $table->text('reason')->nullable();
            $table->text('comment')->nullable();
            
            $table->timestamps();
            
            // Index pour recherche
            $table->index(['signable_type', 'signable_id']);
            $table->index(['user_id', 'signed_at']);
            $table->index(['document_id', 'meaning']);
            $table->index('is_valid');
        });

        // Audit Trail - Complet et infalsifiable
        Schema::create('ged_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Qui
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('user_name', 255)->nullable(); // Nom figé pour historique
            $table->string('user_email', 255)->nullable();
            $table->foreignId('user_role_id')->nullable()->constrained('ged_roles');
            $table->string('user_role_name', 100)->nullable();
            
            // Quoi - Action
            $table->string('action', 100); // create, update, delete, approve, sign, view, download, print, export
            $table->string('action_category', 50); // document, workflow, user, system, access, signature
            $table->string('action_description', 500);
            
            // Sur quoi - Entité cible
            $table->string('auditable_type', 100)->nullable(); // Model class
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('auditable_name', 255)->nullable(); // Nom lisible
            
            // Document concerné (si applicable)
            $table->foreignId('document_id')->nullable()->constrained('ged_documents');
            $table->string('document_number', 50)->nullable();
            $table->string('document_version', 20)->nullable();
            
            // Données de changement (pour modifications)
            $table->json('old_values')->nullable(); // Valeurs avant modification
            $table->json('new_values')->nullable(); // Valeurs après modification
            $table->json('changed_fields')->nullable(); // Liste des champs modifiés
            
            // Contexte additionnel
            $table->json('metadata')->nullable(); // Données contextuelles supplémentaires
            $table->text('comment')->nullable(); // Commentaire utilisateur
            
            // Résultat
            $table->enum('status', ['success', 'failure', 'error', 'warning'])->default('success');
            $table->string('failure_reason', 500)->nullable();
            
            // Quand - Horodatage précis
            $table->timestamp('occurred_at', 6)->useCurrent(); // Précision microseconde
            $table->string('timezone', 50)->default('UTC');
            
            // D'où - Contexte d'accès
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('request_id', 100)->nullable(); // ID unique de requête
            $table->string('request_method', 10)->nullable();
            $table->string('request_url', 500)->nullable();
            
            // Intégrité
            $table->string('previous_hash', 64)->nullable(); // Hash de l'entrée précédente (chain)
            $table->string('entry_hash', 64); // Hash de cette entrée
            
            // Classification GMP
            $table->boolean('is_gmp_critical')->default(false);
            $table->boolean('requires_review')->default(false);
            $table->boolean('is_security_event')->default(false);
            
            // Timestamps
            $table->timestamps();
            
            // Pas de soft delete - L'audit trail est immuable
            
            // Index optimisés
            $table->index(['user_id', 'occurred_at']);
            $table->index(['document_id', 'occurred_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'action_category']);
            $table->index(['occurred_at', 'is_gmp_critical']);
            $table->index('is_security_event');
        });

        // Configuration de l'audit (quelles actions tracer)
        Schema::create('ged_audit_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100)->unique();
            $table->string('category', 50);
            $table->string('description', 500);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_gmp_required')->default(false);
            $table->enum('retention_period', ['1_year', '2_years', '5_years', '10_years', 'permanent'])->default('10_years');
            $table->timestamps();
        });

        // Sessions utilisateur pour traçabilité
        Schema::create('ged_user_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_token', 100)->unique();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->json('device_info')->nullable();
            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('logout_at')->nullable();
            $table->enum('logout_reason', ['user', 'timeout', 'forced', 'system'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('last_activity_at');
        });

        // Tentatives de connexion (sécurité)
        Schema::create('ged_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->boolean('successful')->default(false);
            $table->string('failure_reason', 100)->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ged_login_attempts');
        Schema::dropIfExists('ged_user_sessions');
        Schema::dropIfExists('ged_audit_configurations');
        Schema::dropIfExists('ged_audit_logs');
        Schema::dropIfExists('ged_electronic_signatures');
    }
};
