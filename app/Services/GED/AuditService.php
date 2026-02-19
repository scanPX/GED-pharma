<?php

namespace App\Services\GED;

use App\Models\GED\AuditLog;
use App\Models\GED\Document;
use App\Models\GED\DocumentVersion;
use App\Models\GED\DocumentStatus;
use App\Models\GED\WorkflowInstance;
use App\Models\GED\ElectronicSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * GED Audit Service
 * 
 * Service de traçabilité conforme EU Annex 11 et 21 CFR Part 11
 * Gère l'audit trail complet et infalsifiable
 */
class AuditService
{
    /**
     * Logger une action générique
     * Supports both legacy AuthController calls and standard calls
     */
    public function log(
        string $action,
        ?string $category = null,
        ?string $description = null,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?string $comment = null,
        // Legacy parameters from AuthController
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $details = null,
        ?string $severity = null
    ): AuditLog {
        // Handle legacy AuthController calls
        if ($modelType !== null || $details !== null) {
            $category = $category ?? $this->mapModelTypeToCategory($modelType ?? 'system');
            $description = $description ?? $action;
            $metadata = $details ?? $metadata;
            if ($severity) {
                $metadata['severity'] = $severity;
            }
            if ($modelId) {
                $metadata['model_id'] = $modelId;
            }
        }
        
        return AuditLog::log(
            $action,
            $category ?? 'system',
            $description ?? $action,
            $auditable,
            $oldValues,
            $newValues,
            $metadata,
            $comment
        );
    }
    
    /**
     * Map model type to audit category
     */
    private function mapModelTypeToCategory(string $modelType): string
    {
        return match (strtolower($modelType)) {
            'user' => AuditLog::CATEGORY_ACCESS,
            'document' => AuditLog::CATEGORY_DOCUMENT,
            'workflow' => AuditLog::CATEGORY_WORKFLOW,
            'signature' => AuditLog::CATEGORY_SIGNATURE,
            default => AuditLog::CATEGORY_SYSTEM,
        };
    }

    // ========== DOCUMENT EVENTS ==========

    public function logDocumentCreated(Document $document, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::CATEGORY_DOCUMENT,
            "Document créé: {$document->document_number} - {$document->title}",
            $document,
            [],
            $document->toArray()
        );
    }

    public function logDocumentUpdated(Document $document, array $oldValues, array $newValues, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::CATEGORY_DOCUMENT,
            "Document modifié: {$document->document_number}",
            $document,
            $oldValues,
            $newValues
        );
    }

    public function logDocumentViewed(Document $document, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_VIEW,
            AuditLog::CATEGORY_DOCUMENT,
            "Document consulté: {$document->document_number}",
            $document
        );
    }

    public function logDocumentDownloaded(DocumentVersion $version, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DOWNLOAD,
            AuditLog::CATEGORY_DOCUMENT,
            "Document téléchargé: {$version->document->document_number} v{$version->version_number}",
            $version,
            [],
            [],
            ['file_hash' => $version->file_hash]
        );
    }

    public function logDocumentPrinted(Document $document, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_PRINT,
            AuditLog::CATEGORY_DOCUMENT,
            "Document imprimé: {$document->document_number}",
            $document
        );
    }

    public function logDocumentArchived(Document $document, User $user, string $reason): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::CATEGORY_DOCUMENT,
            "Document archivé: {$document->document_number}",
            $document,
            ['is_archived' => false],
            ['is_archived' => true, 'archive_reason' => $reason]
        );
    }

    public function logStatusChange(
        Document $document, 
        DocumentStatus $oldStatus, 
        DocumentStatus $newStatus, 
        User $user,
        ?string $comment = null
    ): AuditLog {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::CATEGORY_DOCUMENT,
            "Changement de statut: {$document->document_number} - {$oldStatus->name} → {$newStatus->name}",
            $document,
            ['status_id' => $oldStatus->id, 'status_code' => $oldStatus->code],
            ['status_id' => $newStatus->id, 'status_code' => $newStatus->code],
            [],
            $comment
        );
    }

    public function logVersionCreated(DocumentVersion $version, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::CATEGORY_DOCUMENT,
            "Nouvelle version créée: {$version->document->document_number} v{$version->version_number}",
            $version,
            [],
            [
                'version_number' => $version->version_number,
                'change_type' => $version->change_type,
                'file_hash' => $version->file_hash,
            ]
        );
    }

    // ========== WORKFLOW EVENTS ==========

    public function logWorkflowSubmitted(WorkflowInstance $instance, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_SUBMIT,
            AuditLog::CATEGORY_WORKFLOW,
            "Workflow soumis pour approbation: {$instance->document->document_number}",
            $instance,
            [],
            ['workflow_id' => $instance->workflow_id, 'status' => 'pending']
        );
    }

    public function logWorkflowApproved(WorkflowInstance $instance, User $user, int $stepOrder, ?string $comment = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_APPROVE,
            AuditLog::CATEGORY_WORKFLOW,
            "Étape {$stepOrder} approuvée: {$instance->document->document_number}",
            $instance,
            [],
            ['step_order' => $stepOrder, 'action' => 'approved'],
            [],
            $comment
        );
    }

    public function logWorkflowRejected(WorkflowInstance $instance, User $user, string $reason): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_REJECT,
            AuditLog::CATEGORY_WORKFLOW,
            "Workflow rejeté: {$instance->document->document_number}",
            $instance,
            [],
            ['status' => 'rejected'],
            [],
            $reason
        );
    }

    public function logWorkflowCompleted(WorkflowInstance $instance, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_APPROVE,
            AuditLog::CATEGORY_WORKFLOW,
            "Workflow terminé avec succès: {$instance->document->document_number}",
            $instance,
            [],
            ['status' => 'approved']
        );
    }

    // ========== SIGNATURE EVENTS ==========

    public function logSignatureApplied(ElectronicSignature $signature, User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_SIGN,
            AuditLog::CATEGORY_SIGNATURE,
            "Signature électronique appliquée: {$signature->meaning} par {$signature->user_full_name}",
            $signature,
            [],
            [
                'meaning' => $signature->meaning,
                'signature_hash' => $signature->signature_hash,
                'document_hash' => $signature->document_hash,
                'authentication_method' => $signature->authentication_method,
            ]
        );
    }

    public function logSignatureRevoked(ElectronicSignature $signature, User $revokedBy, string $reason): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::CATEGORY_SIGNATURE,
            "Signature électronique révoquée: {$signature->uuid}",
            $signature,
            ['is_revoked' => false],
            ['is_revoked' => true, 'revocation_reason' => $reason]
        );
    }

    // ========== ACCESS EVENTS ==========

    public function logLogin(User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_LOGIN,
            AuditLog::CATEGORY_ACCESS,
            "Connexion utilisateur: {$user->email}",
            $user
        );
    }

    public function logLogout(User $user): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_LOGOUT,
            AuditLog::CATEGORY_ACCESS,
            "Déconnexion utilisateur: {$user->email}",
            $user
        );
    }

    public function logLoginFailed(string $email, string $reason): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_LOGIN_FAILED,
            AuditLog::CATEGORY_ACCESS,
            "Tentative de connexion échouée: {$email}",
            null,
            [],
            [],
            ['email' => $email, 'reason' => $reason]
        );
    }

    public function logAccessGranted(Document $document, User $targetUser, User $grantedBy, string $level): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::CATEGORY_ACCESS,
            "Accès accordé sur {$document->document_number} à {$targetUser->name}",
            $document,
            [],
            ['user_id' => $targetUser->id, 'access_level' => $level]
        );
    }

    // ========== AUDIT TRAIL INTEGRITY ==========

    /**
     * Vérifier l'intégrité de l'audit trail
     */
    public function verifyIntegrity(int $fromId = 1): array
    {
        return AuditLog::verifyChainIntegrity($fromId);
    }

    /**
     * Générer un rapport d'audit pour une période
     */
    public function generateAuditReport(\DateTime $startDate, \DateTime $endDate, array $filters = []): array
    {
        $query = AuditLog::inDateRange($startDate, $endDate);

        if (!empty($filters['document_id'])) {
            $query->forDocument($filters['document_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (!empty($filters['category'])) {
            $query->forCategory($filters['category']);
        }

        if (!empty($filters['action'])) {
            $query->forAction($filters['action']);
        }

        if (!empty($filters['gmp_critical_only'])) {
            $query->gmpCritical();
        }

        $logs = $query->orderBy('occurred_at')->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
            ],
            'total_entries' => $logs->count(),
            'filters_applied' => $filters,
            'integrity_verified' => count($this->verifyIntegrity()) === 0,
            'entries' => $logs,
            'generated_at' => now()->toIso8601String(),
            'generated_by' => auth()->user()?->name ?? 'System',
        ];
    }
}
