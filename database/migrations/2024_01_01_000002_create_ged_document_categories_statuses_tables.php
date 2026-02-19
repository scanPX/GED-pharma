<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GED - Gestion Électronique de Documents
 * Migration: Document Categories and Statuses
 * 
 * Conformité: GMP Annex 11, ICH Q10, ISO 13485
 * Objectif: Classification documentaire pharmaceutique
 */
return new class extends Migration
{
    public function up(): void
    {
        // Catégories documentaires - Classification GMP
        Schema::create('ged_document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // SOP, VR, SPEC, BATCH, DEV
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('ged_document_categories')->onDelete('set null');
            $table->integer('sort_order')->default(0);
            $table->string('prefix', 10)->nullable(); // Préfixe pour numérotation automatique
            $table->string('default_workflow', 50)->nullable(); // Workflow par défaut
            $table->integer('retention_years')->default(10); // Durée de rétention réglementaire
            $table->boolean('requires_training')->default(false); // Lecture obligatoire formalisée
            $table->boolean('is_gmp_critical')->default(false); // Document critique GMP
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['parent_id', 'sort_order']);
            $table->index('is_gmp_critical');
        });

        // Statuts documentaires - Cycle de vie GMP
        Schema::create('ged_document_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // DRAFT, REVIEW, APPROVED, EFFECTIVE, OBSOLETE
            $table->string('name', 100);
            $table->string('color', 7)->default('#6B7280'); // Couleur hex pour UI
            $table->string('icon', 50)->nullable(); // Icône pour UI
            $table->text('description')->nullable();
            $table->boolean('is_editable')->default(true); // Document modifiable dans ce statut
            $table->boolean('is_visible_to_all')->default(false); // Visible par tous les utilisateurs
            $table->boolean('requires_signature')->default(false); // Transition nécessitant signature
            $table->boolean('triggers_training')->default(false); // Déclenche formation obligatoire
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['sort_order', 'is_active']);
        });

        // Types de documents
        Schema::create('ged_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('ged_document_categories')->onDelete('cascade');
            $table->json('allowed_extensions')->nullable(); // [".pdf", ".docx"]
            $table->integer('max_file_size_mb')->default(50);
            $table->boolean('requires_electronic_signature')->default(false);
            $table->string('numbering_format', 100)->nullable(); // SOP-{CATEGORY}-{YEAR}-{SEQ:4}
            $table->integer('review_period_months')->default(24); // Période de revue périodique
            $table->boolean('is_controlled')->default(true); // Document contrôlé GMP
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ged_document_types');
        Schema::dropIfExists('ged_document_statuses');
        Schema::dropIfExists('ged_document_categories');
    }
};
