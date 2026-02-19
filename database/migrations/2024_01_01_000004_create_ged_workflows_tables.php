<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GED - Gestion Électronique de Documents
 * Migration: Workflows d'approbation multi-niveaux
 * 
 * Conformité: GMP Annex 11, 21 CFR Part 11, ISO 13485
 * Objectif: Workflows d'approbation configurables QA/Réglementaire
 */
return new class extends Migration
{
    public function up(): void
    {
        // Définition des workflows
        Schema::create('ged_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // SOP_APPROVAL, VR_VALIDATION
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('type', [
                'approval',      // Approbation standard
                'review',        // Revue simple
                'validation',    // Validation qualité
                'change_control' // Change control
            ])->default('approval');
            
            // Configuration
            $table->boolean('requires_sequential_approval')->default(true); // Étapes séquentielles
            $table->boolean('allows_parallel_approval')->default(false); // Approbations parallèles
            $table->boolean('requires_all_approvers')->default(true); // Tous doivent approuver
            $table->integer('min_approvers')->default(1);
            $table->boolean('allows_delegation')->default(false);
            $table->boolean('allows_rejection')->default(true);
            $table->boolean('allows_revision_request')->default(true);
            
            // Escalade
            $table->integer('escalation_days')->nullable(); // Jours avant escalade
            $table->foreignId('escalation_role_id')->nullable()->constrained('ged_roles');
            
            // Notifications
            $table->boolean('notify_on_submit')->default(true);
            $table->boolean('notify_on_approve')->default(true);
            $table->boolean('notify_on_reject')->default(true);
            $table->boolean('notify_on_complete')->default(true);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_active');
        });

        // Étapes du workflow
        Schema::create('ged_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('ged_workflows')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->integer('step_order'); // Ordre de l'étape
            
            // Configuration de l'étape
            $table->enum('step_type', [
                'review',           // Revue simple
                'approval',         // Approbation
                'signature',        // Signature électronique requise
                'qa_approval',      // Approbation QA spécifique
                'regulatory_approval', // Approbation Affaires Réglementaires
                'final_approval'    // Approbation finale
            ])->default('approval');
            
            // Approbateurs autorisés
            $table->foreignId('required_role_id')->nullable()->constrained('ged_roles'); // Rôle requis
            $table->foreignId('required_user_id')->nullable()->constrained('users'); // Utilisateur spécifique
            $table->json('allowed_roles')->nullable(); // Liste des rôles autorisés
            $table->boolean('any_user_with_permission')->default(false);
            
            // Règles
            $table->boolean('requires_comment')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->integer('timeout_days')->nullable(); // Délai maximum
            $table->boolean('can_be_skipped')->default(false);
            $table->string('skip_condition', 500)->nullable(); // Condition de skip
            
            // Statut transitionné après approbation
            $table->foreignId('target_status_id')->nullable()->constrained('ged_document_statuses');
            $table->foreignId('rejection_status_id')->nullable()->constrained('ged_document_statuses');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['workflow_id', 'step_order']);
            $table->index(['workflow_id', 'is_active']);
        });

        // Association workflow-catégorie de document
        Schema::create('ged_workflow_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('ged_workflows')->onDelete('cascade');
            $table->foreignId('document_type_id')->constrained('ged_document_types')->onDelete('cascade');
            $table->boolean('is_default')->default(false); // Workflow par défaut pour ce type
            $table->timestamps();
            
            $table->unique(['workflow_id', 'document_type_id']);
        });

        // Instances de workflow (workflow en cours)
        Schema::create('ged_workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('ged_workflows');
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->foreignId('document_version_id')->constrained('ged_document_versions');
            
            // Initiateur
            $table->foreignId('initiated_by')->constrained('users');
            $table->timestamp('initiated_at')->useCurrent();
            
            // État
            $table->enum('status', [
                'draft',        // Brouillon non soumis
                'pending',      // En attente d'approbation
                'in_progress',  // En cours de traitement
                'approved',     // Approuvé
                'rejected',     // Rejeté
                'cancelled',    // Annulé
                'expired'       // Expiré (timeout)
            ])->default('draft');
            
            $table->integer('current_step_order')->default(1);
            $table->foreignId('current_step_id')->nullable()->constrained('ged_workflow_steps');
            
            // Dates
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_date')->nullable();
            
            // Résultat
            $table->text('final_comment')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['document_id', 'status']);
            $table->index(['status', 'current_step_order']);
        });

        // Actions sur les étapes du workflow
        Schema::create('ged_workflow_step_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('ged_workflow_instances')->onDelete('cascade');
            $table->foreignId('workflow_step_id')->constrained('ged_workflow_steps');
            $table->integer('step_order');
            
            // Acteur
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('on_behalf_of')->nullable()->constrained('users'); // Délégation
            
            // Action
            $table->enum('action', [
                'submitted',    // Soumis pour approbation
                'approved',     // Approuvé
                'rejected',     // Rejeté
                'revision_requested', // Demande de révision
                'commented',    // Commentaire ajouté
                'delegated',    // Délégué à un autre utilisateur
                'escalated',    // Escaladé
                'skipped',      // Étape sautée
                'timed_out'     // Timeout atteint
            ]);
            
            $table->text('comment')->nullable();
            $table->timestamp('action_at')->useCurrent();
            
            // Signature électronique
            $table->boolean('signature_required')->default(false);
            $table->boolean('signature_provided')->default(false);
            $table->foreignId('signature_id')->nullable();
            
            // Contexte audit trail
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            
            $table->timestamps();
            
            $table->index(['workflow_instance_id', 'step_order']);
            $table->index(['user_id', 'action']);
        });

        // Commentaires de revue
        Schema::create('ged_review_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->foreignId('document_version_id')->constrained('ged_document_versions');
            $table->foreignId('workflow_instance_id')->nullable()->constrained('ged_workflow_instances');
            $table->foreignId('user_id')->constrained('users');
            
            // Contenu
            $table->text('comment');
            $table->enum('type', [
                'general',      // Commentaire général
                'suggestion',   // Suggestion
                'correction',   // Correction requise
                'clarification', // Demande de clarification
                'approval',     // Commentaire d'approbation
                'rejection'     // Motif de rejet
            ])->default('general');
            
            $table->enum('severity', ['info', 'minor', 'major', 'critical'])->default('info');
            
            // Position dans le document (optionnel)
            $table->integer('page_number')->nullable();
            $table->string('section_reference', 100)->nullable();
            
            // Résolution
            $table->boolean('requires_action')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_comment')->nullable();
            
            // Réponse
            $table->foreignId('parent_id')->nullable()->constrained('ged_review_comments');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['document_id', 'is_resolved']);
            $table->index(['workflow_instance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ged_review_comments');
        Schema::dropIfExists('ged_workflow_step_actions');
        Schema::dropIfExists('ged_workflow_instances');
        Schema::dropIfExists('ged_workflow_document_types');
        Schema::dropIfExists('ged_workflow_steps');
        Schema::dropIfExists('ged_workflows');
    }
};
