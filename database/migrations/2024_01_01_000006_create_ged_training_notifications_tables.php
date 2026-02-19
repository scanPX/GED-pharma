<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * GED - Gestion Électronique de Documents
 * Migration: Formations, Notifications et Recherche
 * 
 * Conformité: GMP Training Requirements, ICH Q10
 * Objectif: Gestion des formations documentaires et système de notification
 */
return new class extends Migration
{
    public function up(): void
    {
        // Formation documentaire - Lecture obligatoire formalisée
        Schema::create('ged_training_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('document_id')->constrained('ged_documents');
            $table->foreignId('document_version_id')->constrained('ged_document_versions');
            
            // Statut formation
            $table->enum('status', [
                'assigned',     // Formation assignée
                'in_progress',  // En cours de lecture
                'completed',    // Lecture terminée
                'acknowledged', // Prise de connaissance confirmée
                'overdue',      // En retard
                'exempted'      // Exempté
            ])->default('assigned');
            
            // Dates
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            
            // Assignation
            $table->foreignId('assigned_by')->constrained('users');
            $table->string('assignment_reason', 500)->nullable();
            
            // Complétion
            $table->integer('time_spent_minutes')->default(0);
            $table->boolean('quiz_passed')->nullable();
            $table->integer('quiz_score')->nullable();
            $table->foreignId('signature_id')->nullable()->constrained('ged_electronic_signatures');
            
            // Exemption
            $table->foreignId('exempted_by')->nullable()->constrained('users');
            $table->string('exemption_reason', 500)->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'document_version_id']);
            $table->index(['user_id', 'status']);
            $table->index(['document_id', 'status']);
            $table->index('due_date');
        });

        // Notifications système
        Schema::create('ged_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            
            // Type et priorité
            $table->enum('type', [
                'document_assigned',      // Document assigné pour action
                'approval_required',      // Approbation requise
                'approval_received',      // Approbation reçue
                'document_rejected',      // Document rejeté
                'document_approved',      // Document approuvé
                'training_assigned',      // Formation assignée
                'training_due',          // Formation bientôt due
                'training_overdue',      // Formation en retard
                'review_reminder',       // Rappel de revue périodique
                'document_expiring',     // Document bientôt expiré
                'comment_added',         // Commentaire ajouté
                'workflow_completed',    // Workflow terminé
                'system_alert',          // Alerte système
                'access_granted'         // Accès accordé
            ]);
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Contenu
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable(); // Données additionnelles
            
            // Lien vers entité
            $table->string('notifiable_type', 100)->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->string('action_url', 500)->nullable();
            
            // Statut
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            
            // Email
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['type', 'priority']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });

        // Préférences de notification par utilisateur
        Schema::create('ged_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('notification_type', 50);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->enum('email_frequency', ['immediate', 'daily', 'weekly'])->default('immediate');
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_type']);
        });

        // Index de recherche avancée
        Schema::create('ged_search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->foreignId('document_version_id')->nullable()->constrained('ged_document_versions');
            
            // Contenu indexé
            $table->text('searchable_content'); // Contenu textuel extrait
            $table->json('keywords')->nullable();
            $table->json('metadata_values')->nullable();
            
            // Informations pour filtrage
            $table->string('document_number', 50);
            $table->string('title', 500);
            $table->string('category_code', 20);
            $table->string('type_code', 30);
            $table->string('status_code', 30);
            $table->string('department', 100)->nullable();
            $table->string('owner_name', 255);
            $table->string('current_version', 20);
            $table->date('effective_date')->nullable();
            
            $table->timestamps();
            
            // Fulltext index only for MySQL/MariaDB
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
                $table->fullText(['searchable_content', 'title']);
            }
            $table->index(['category_code', 'status_code']);
            $table->index(['department', 'type_code']);
        });

        // Filtres sauvegardés par utilisateur
        Schema::create('ged_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('filters'); // Critères de recherche sauvegardés
            $table->json('columns')->nullable(); // Colonnes à afficher
            $table->string('sort_by', 50)->nullable();
            $table->string('sort_direction', 4)->default('asc');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_default']);
        });

        // Favoris utilisateur
        Schema::create('ged_user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'document_id']);
        });

        // Historique de consultation (pour documents récents)
        Schema::create('ged_document_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('document_id')->constrained('ged_documents')->onDelete('cascade');
            $table->foreignId('document_version_id')->nullable()->constrained('ged_document_versions');
            $table->timestamp('viewed_at')->useCurrent();
            $table->integer('duration_seconds')->nullable();
            $table->string('ip_address', 45)->nullable();
            
            $table->index(['user_id', 'viewed_at']);
            $table->index(['document_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ged_document_views');
        Schema::dropIfExists('ged_user_favorites');
        Schema::dropIfExists('ged_saved_searches');
        Schema::dropIfExists('ged_search_index');
        Schema::dropIfExists('ged_notification_preferences');
        Schema::dropIfExists('ged_notifications');
        Schema::dropIfExists('ged_training_records');
    }
};
