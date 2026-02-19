<?php

namespace App\Services\GED;

use App\Models\GED\Workflow;
use App\Models\GED\WorkflowStep;
use App\Models\GED\WorkflowInstance;
use App\Models\GED\WorkflowStepAction;
use App\Models\GED\Document;
use App\Models\GED\DocumentVersion;
use App\Models\GED\DocumentStatus;
use App\Models\GED\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * GED Workflow Service
 * 
 * Gestion des workflows d'approbation multi-niveaux
 * Conformité: GMP - Processus d'approbation formalisés
 */
class WorkflowService
{
    protected AuditService $auditService;
    protected SignatureService $signatureService;

    public function __construct(AuditService $auditService, SignatureService $signatureService)
    {
        $this->auditService = $auditService;
        $this->signatureService = $signatureService;
    }

    /**
     * Initier un workflow sur un document
     */
    public function initiateWorkflow(Document $document, Workflow $workflow, User $user): WorkflowInstance
    {
        // Vérifier qu'il n'y a pas de workflow actif
        if ($document->getActiveWorkflow()) {
            throw new \Exception('Un workflow est déjà en cours pour ce document.');
        }

        $currentVersion = $document->currentVersionRelation;
        if (!$currentVersion) {
            throw new \Exception('Le document n\'a pas de version courante.');
        }

        $instance = WorkflowInstance::create([
            'workflow_id' => $workflow->id,
            'document_id' => $document->id,
            'document_version_id' => $currentVersion->id,
            'initiated_by' => $user->id,
            'status' => WorkflowInstance::STATUS_DRAFT,
            'current_step_order' => 0,
        ]);

        return $instance;
    }

    /**
     * Soumettre un workflow pour approbation
     */
    public function submitWorkflow(WorkflowInstance $instance, User $user): WorkflowInstance
    {
        if ($instance->status !== WorkflowInstance::STATUS_DRAFT) {
            throw new \Exception('Ce workflow a déjà été soumis.');
        }

        return DB::transaction(function () use ($instance, $user) {
            $instance->submit();

            // Créer l'action de soumission
            $firstStep = $instance->workflow->getFirstStep();
            if ($firstStep) {
                WorkflowStepAction::create([
                    'workflow_instance_id' => $instance->id,
                    'workflow_step_id' => $firstStep->id,
                    'step_order' => $firstStep->step_order,
                    'user_id' => $user->id,
                    'action' => WorkflowStepAction::ACTION_SUBMITTED,
                    'action_at' => now(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Notifier les approbateurs potentiels
                $this->notifyStepApprovers($instance, $firstStep);
            }

            // Mettre à jour le statut du document
            $this->updateDocumentStatus($instance->document, DocumentStatus::PENDING_APPROVAL);

            // Audit
            $this->auditService->logWorkflowSubmitted($instance, $user);

            return $instance->fresh();
        });
    }

    /**
     * Approuver une étape du workflow
     */
    public function approveStep(
        WorkflowInstance $instance, 
        User $user, 
        ?string $comment = null,
        ?string $signaturePin = null
    ): WorkflowInstance {
        $currentStep = $instance->currentStep;
        
        if (!$currentStep) {
            throw new \Exception('Aucune étape en cours.');
        }

        if (!$currentStep->canUserExecute($user)) {
            throw new \Exception('Vous n\'êtes pas autorisé à approuver cette étape.');
        }

        return DB::transaction(function () use ($instance, $currentStep, $user, $comment, $signaturePin) {
            $signatureId = null;

            // Signature électronique si requise
            if ($currentStep->requiresElectronicSignature()) {
                if (!$signaturePin) {
                    throw new \Exception('Une signature électronique est requise pour cette étape.');
                }

                $signature = $this->signatureService->createSignature(
                    $user,
                    $instance,
                    'approved',
                    $signaturePin,
                    $comment
                );
                $signatureId = $signature->id;
            }

            // Créer l'action d'approbation
            WorkflowStepAction::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'step_order' => $currentStep->step_order,
                'user_id' => $user->id,
                'action' => WorkflowStepAction::ACTION_APPROVED,
                'comment' => $comment,
                'action_at' => now(),
                'signature_required' => $currentStep->requiresElectronicSignature(),
                'signature_provided' => $signatureId !== null,
                'signature_id' => $signatureId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Audit
            $this->auditService->logWorkflowApproved($instance, $user, $currentStep->step_order, $comment);

            // Appliquer le statut cible si défini
            if ($currentStep->target_status_id) {
                $instance->document->update(['status_id' => $currentStep->target_status_id]);
            }

            // Passer à l'étape suivante
            $hasNext = $instance->advanceToNextStep();

            if ($hasNext) {
                // Notifier les approbateurs de l'étape suivante
                $this->notifyStepApprovers($instance, $instance->currentStep);
            } else {
                // Workflow terminé avec succès
                $this->completeWorkflow($instance, $user);
            }

            return $instance->fresh();
        });
    }

    /**
     * Rejeter le workflow
     */
    public function rejectWorkflow(
        WorkflowInstance $instance, 
        User $user, 
        string $reason,
        ?string $signaturePin = null
    ): WorkflowInstance {
        $currentStep = $instance->currentStep;

        if (!$currentStep || !$currentStep->canUserExecute($user)) {
            throw new \Exception('Vous n\'êtes pas autorisé à rejeter ce workflow.');
        }

        return DB::transaction(function () use ($instance, $currentStep, $user, $reason, $signaturePin) {
            $signatureId = null;

            // Signature si requise
            if ($currentStep->requiresElectronicSignature() && $signaturePin) {
                $signature = $this->signatureService->createSignature(
                    $user,
                    $instance,
                    'rejected',
                    $signaturePin,
                    $reason
                );
                $signatureId = $signature->id;
            }

            // Créer l'action de rejet
            WorkflowStepAction::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'step_order' => $currentStep->step_order,
                'user_id' => $user->id,
                'action' => WorkflowStepAction::ACTION_REJECTED,
                'comment' => $reason,
                'action_at' => now(),
                'signature_required' => $currentStep->requiresElectronicSignature(),
                'signature_provided' => $signatureId !== null,
                'signature_id' => $signatureId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Rejeter le workflow
            $instance->reject($reason, $user->id);

            // Appliquer le statut de rejet
            if ($currentStep->rejection_status_id) {
                $instance->document->update(['status_id' => $currentStep->rejection_status_id]);
            } else {
                $this->updateDocumentStatus($instance->document, DocumentStatus::DRAFT);
            }

            // Notifier l'initiateur
            $this->notifyWorkflowRejected($instance, $reason);

            // Audit
            $this->auditService->logWorkflowRejected($instance, $user, $reason);

            return $instance->fresh();
        });
    }

    /**
     * Demander une révision
     */
    public function requestRevision(
        WorkflowInstance $instance, 
        User $user, 
        string $comment
    ): WorkflowInstance {
        $currentStep = $instance->currentStep;

        if (!$currentStep || !$currentStep->canUserExecute($user)) {
            throw new \Exception('Vous n\'êtes pas autorisé à demander une révision.');
        }

        return DB::transaction(function () use ($instance, $currentStep, $user, $comment) {
            WorkflowStepAction::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'step_order' => $currentStep->step_order,
                'user_id' => $user->id,
                'action' => WorkflowStepAction::ACTION_REVISION_REQUESTED,
                'comment' => $comment,
                'action_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Remettre le workflow en brouillon
            $instance->status = WorkflowInstance::STATUS_DRAFT;
            $instance->current_step_order = 0;
            $instance->current_step_id = null;
            $instance->save();

            // Remettre le document en brouillon
            $this->updateDocumentStatus($instance->document, DocumentStatus::DRAFT);

            // Notifier l'initiateur
            Notification::notify(
                $instance->initiated_by,
                Notification::TYPE_DOCUMENT_REJECTED,
                'Révision demandée',
                "Une révision a été demandée pour {$instance->document->document_number}: {$comment}",
                Notification::PRIORITY_HIGH,
                $instance->document,
                route('ged.documents.show', $instance->document)
            );

            // Audit
            $this->auditService->log(
                'workflow_revision_requested',
                'workflow',
                "Revision requested for document {$instance->document->document_number} at step '{$currentStep->name}'",
                $instance,
                ['status' => WorkflowInstance::STATUS_IN_PROGRESS],
                ['status' => WorkflowInstance::STATUS_DRAFT, 'comment' => $comment],
                [],
                $comment
            );

            return $instance->fresh();
        });
    }

    /**
     * Annuler un workflow
     */
    public function cancelWorkflow(WorkflowInstance $instance, User $user, string $reason = null): WorkflowInstance
    {
        if (!$instance->isActive()) {
            throw new \Exception('Ce workflow n\'est plus actif.');
        }

        // Seul l'initiateur ou un admin peut annuler
        if ($instance->initiated_by !== $user->id && !$user->hasPermission('workflow.manage')) {
            throw new \Exception('Vous n\'êtes pas autorisé à annuler ce workflow.');
        }

        $instance->cancel($reason);
        $this->updateDocumentStatus($instance->document, DocumentStatus::DRAFT);

        return $instance->fresh();
    }

    /**
     * Obtenir les workflows en attente pour un utilisateur
     */
    public function getPendingWorkflowsForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $userRoleIds = $user->gedRoles->pluck('id')->toArray();
        $userRoleNames = $user->gedRoles->pluck('name')->toArray();
        $hasGlobalPermission = $user->hasPermission('workflow.approve');

        return WorkflowInstance::active()
            ->with(['document', 'workflow', 'currentStep'])
            ->whereHas('currentStep', function ($query) use ($user, $userRoleIds, $userRoleNames, $hasGlobalPermission) {
                $query->where(function ($q) use ($user, $userRoleIds, $userRoleNames, $hasGlobalPermission) {
                    // 1. Utilisateur spécifique
                    $q->where('required_user_id', $user->id);

                    // 2. Rôle spécifique
                    if (!empty($userRoleIds)) {
                        $q->orWhereIn('required_role_id', $userRoleIds);
                    }

                    // 3. Un des rôles autorisés (JSON array)
                    foreach ($userRoleNames as $roleName) {
                        $q->orWhereJsonContains('allowed_roles', $roleName);
                    }

                    // 4. Tout utilisateur avec permission
                    if ($hasGlobalPermission) {
                        $q->orWhere('any_user_with_permission', true);
                    }
                });
            })
            ->orderBy('created_at')
            ->get();
    }

    // ========== MÉTHODES PRIVÉES ==========

    /**
     * Compléter un workflow avec succès
     */
    protected function completeWorkflow(WorkflowInstance $instance, User $user): void
    {
        $instance->complete($user->id);

        // Passer le document en approuvé/effectif
        $this->updateDocumentStatus($instance->document, DocumentStatus::APPROVED);

        // Marquer la version comme approuvée
        $instance->documentVersion->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        // Notifier l'initiateur
        Notification::notify(
            $instance->initiated_by,
            Notification::TYPE_WORKFLOW_COMPLETED,
            'Workflow terminé',
            "Le workflow d'approbation pour {$instance->document->document_number} est terminé avec succès.",
            Notification::PRIORITY_NORMAL,
            $instance->document,
            route('ged.documents.show', $instance->document)
        );

        // Audit
        $this->auditService->logWorkflowCompleted($instance, $user);
    }

    /**
     * Notifier les approbateurs d'une étape
     */
    protected function notifyStepApprovers(WorkflowInstance $instance, WorkflowStep $step): void
    {
        $users = $this->getStepApprovers($step);

        foreach ($users as $user) {
            Notification::notify(
                $user->id,
                Notification::TYPE_APPROVAL_REQUIRED,
                'Approbation requise',
                "Votre approbation est requise pour {$instance->document->document_number}",
                Notification::PRIORITY_HIGH,
                $instance->document,
                route('ged.workflows.show', $instance)
            );
        }
    }

    /**
     * Notifier le rejet du workflow
     */
    protected function notifyWorkflowRejected(WorkflowInstance $instance, string $reason): void
    {
        Notification::notify(
            $instance->initiated_by,
            Notification::TYPE_DOCUMENT_REJECTED,
            'Document rejeté',
            "Le document {$instance->document->document_number} a été rejeté: {$reason}",
            Notification::PRIORITY_HIGH,
            $instance->document,
            route('ged.documents.show', $instance->document)
        );
    }

    /**
     * Obtenir les approbateurs potentiels pour une étape
     */
    protected function getStepApprovers(WorkflowStep $step): \Illuminate\Database\Eloquent\Collection
    {
        $usersQuery = User::active();

        if ($step->required_user_id) {
            return $usersQuery->where('id', $step->required_user_id)->get();
        }

        $usersQuery->where(function($q) use ($step) {
            // Rôle requis
            if ($step->required_role_id) {
                $q->orWhereHas('gedRoles', fn($sq) => $sq->where('ged_roles.id', $step->required_role_id));
            }

            // Rôles autorisés
            if ($step->allowed_roles && count($step->allowed_roles) > 0) {
                $q->orWhereHas('gedRoles', fn($sq) => $sq->whereIn('name', $step->allowed_roles));
            }

            // Permission globale
            if ($step->any_user_with_permission) {
                // On récupère les utilisateurs ayant au moins un rôle avec cette permission
                $q->orWhereHas('gedRoles', function($sq) {
                    // Supposons que Role::hasPermission est une base de données, mais c'est souvent une méthode Laravel
                    // Si on veut faire ça proprement en SQL, on doit joindre les permissions si elles sont en DB.
                    // Pour simplifier, on va se baser sur les rôles qui ont can_approve_documents ou similaire
                    $sq->where('can_approve_documents', true);
                });
            }
        });

        return $usersQuery->get();
    }

    /**
     * Mettre à jour le statut du document
     */
    protected function updateDocumentStatus(Document $document, string $statusCode): void
    {
        $status = DocumentStatus::where('code', $statusCode)->first();
        if ($status) {
            $document->update(['status_id' => $status->id]);
        }
    }
}
