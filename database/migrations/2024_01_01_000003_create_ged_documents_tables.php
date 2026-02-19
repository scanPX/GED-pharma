<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * GED - Gestion Électronique de Documents
 * Migration: Documents and Versions Tables
 * 
 * Conformité: GMP Annex 11, 21 CFR Part 11, ISO 13485
 * Objectif: Gestion du cycle de vie documentaire avec versioning contrôlé
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table principale des documents
        Schema::create('ged_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Identifiant unique immuable
            $table->string('document_number', 50)->unique(); // Numéro contrôlé (ex: SOP-QA-2024-0001)
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('ged_document_categories');
            $table->foreignId('type_id')->constrained('ged_document_types');
            $table->foreignId('status_id')->constrained('ged_document_statuses');
            $table->foreignId('owner_id')->constrained('users'); // Propriétaire du document
            $table->foreignId('author_id')->constrained('users'); // Auteur original
            
            // Versioning
            $table->string('current_version', 20)->default('1.0'); // Version effective actuelle
            $table->integer('major_version')->default(1);
            $table->integer('minor_version')->default(0);
            $table->unsignedBigInteger('current_version_id')->nullable(); // FK ajoutée après création table versions
            
            // Dates critiques GMP
            $table->date('effective_date')->nullable(); // Date d'entrée en vigueur
            $table->date('review_date')->nullable(); // Prochaine revue périodique
            $table->date('expiry_date')->nullable(); // Date d'expiration/obsolescence
            $table->date('last_reviewed_at')->nullable();
            
            // Classification
            $table->enum('confidentiality', ['public', 'internal', 'confidential', 'restricted'])->default('internal');
            $table->boolean('is_gmp_critical')->default(false);
            $table->boolean('is_controlled')->default(true);
            $table->boolean('requires_training')->default(false);
            $table->string('language', 5)->default('fr'); // ISO 639-1
            
            // Métadonnées recherche
            $table->string('department', 100)->nullable();
            $table->string('process_area', 100)->nullable(); // Zone de process
            $table->string('equipment_id', 100)->nullable(); // Équipement associé
            $table->json('keywords')->nullable(); // Mots-clés pour recherche
            $table->json('regulatory_references')->nullable(); // Références réglementaires
            
            // Archivage
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users');
            $table->string('archive_reason', 500)->nullable();
            
            // Timestamps avec précision
            $table->timestamps();
            $table->softDeletes();
            
            // Index optimisés pour recherche
            $table->index(['status_id', 'category_id']);
            $table->index(['owner_id', 'status_id']);
            $table->index(['effective_date', 'expiry_date']);
            $table->index(['department', 'process_area']);
            $table->index('is_gmp_critical');
            
            // Fulltext index only for MySQL/MariaDB
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
                $table->fullText(['title', 'description']);
            }
        });

        // Versions documentaires - Historique complet
        Schema::create('ged_document_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->string('version_number', 20); // 1.0, 1.1, 2.0
            $table->integer('major_version');
            $table->integer('minor_version');
            
            // Fichier
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('file_extension', 10);
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('mime_type', 100);
            $table->string('file_hash', 64); // SHA-256 pour intégrité
            
            // Métadonnées version
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('status_id')->constrained('ged_document_statuses');
            $table->text('change_summary')->nullable(); // Résumé des modifications
            $table->text('change_justification')->nullable(); // Justification GMP
            $table->enum('change_type', ['major', 'minor', 'editorial'])->default('minor');
            
            // Approbations
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            
            // Statut version
            $table->boolean('is_current')->default(false); // Version en cours
            $table->boolean('is_effective')->default(false); // Version effective
            $table->boolean('is_draft')->default(true);
            $table->boolean('is_obsolete')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Contraintes
            $table->unique(['document_id', 'version_number']);
            $table->index(['document_id', 'is_current']);
            $table->index(['document_id', 'is_effective']);
            $table->index(['status_id', 'is_obsolete']);
        });

        // Ajout de la FK current_version_id sur documents
        Schema::table('ged_documents', function (Blueprint $table) {
            $table->foreign('current_version_id')
                  ->references('id')
                  ->on('ged_document_versions')
                  ->onDelete('set null');
        });

        // Métadonnées personnalisées par document
        Schema::create('ged_document_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('value_type', 20)->default('string'); // string, number, date, boolean, json
            $table->boolean('is_searchable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            
            $table->unique(['document_id', 'key']);
            $table->index(['key', 'is_searchable']);
        });

        // Relations entre documents
        Schema::create('ged_document_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->foreignId('target_document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->enum('relation_type', [
                'references',      // Le document source référence la cible
                'supersedes',      // Le document source remplace la cible
                'is_superseded_by', // Le document source est remplacé par la cible
                'related_to',      // Relation générique
                'derived_from',    // Dérivé de
                'annexe_of'        // Annexe de
            ]);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->unique(['source_document_id', 'target_document_id', 'relation_type'], 'doc_relations_unique');
        });

        // Contrôle d'accès au niveau document
        Schema::create('ged_document_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('ged_roles')->onDelete('cascade');
            $table->enum('access_level', ['read', 'write', 'approve', 'manage'])->default('read');
            $table->foreignId('granted_by')->constrained('users');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['document_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('ged_documents', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        
        Schema::dropIfExists('ged_document_access');
        Schema::dropIfExists('ged_document_relations');
        Schema::dropIfExists('ged_document_metadata');
        Schema::dropIfExists('ged_document_versions');
        Schema::dropIfExists('ged_documents');
    }
};
