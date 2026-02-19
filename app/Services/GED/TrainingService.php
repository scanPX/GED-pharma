<?php

namespace App\Services\GED;

use App\Models\GED\TrainingRecord;
use App\Models\GED\Document;
use App\Models\GED\DocumentVersion;
use App\Models\GED\ElectronicSignature;
use App\Models\GED\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * GED Training Service
 * 
 * Gestion des formations documentaires et lectures obligatoires
 * Conformité: GMP Training management (ICH Q10)
 */
class TrainingService
{
    protected AuditService $auditService;
    protected SignatureService $signatureService;

    public function __construct(AuditService $auditService, SignatureService $signatureService)
    {
        $this->auditService = $auditService;
        $this->signatureService = $signatureService;
    }

    /**
     * Assigner une formation à un ou plusieurs utilisateurs
     */
    public function assignTraining(Document $document, array $userIds, User $assignedBy, ?string $reason = null, ?string $dueDate = null): Collection
    {
        $records = new Collection();
        $version = $document->currentVersionRelation;

        if (!$version) {
            throw new \Exception('Le document doit avoir une version approuvée pour être assigné en formation.');
        }

        foreach ($userIds as $userId) {
            $record = TrainingRecord::updateOrCreate(
                ['user_id' => $userId, 'document_version_id' => $version->id],
                [
                    'document_id' => $document->id,
                    'status' => TrainingRecord::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                    'due_date' => $dueDate,
                    'assigned_by' => $assignedBy->id,
                    'assignment_reason' => $reason,
                ]
            );

            // Notification
            Notification::notify(
                $userId,
                Notification::TYPE_TRAINING_ASSIGNED,
                'Nouvelle formation assignée',
                "Vous avez été assigné à la formation pour le document {$document->document_number}: {$document->title}",
                Notification::PRIORITY_NORMAL,
                $document
            );

            $records->push($record);
        }

        // Audit
        $this->auditService->log('training_assigned', 'training', "Training assigned for document {$document->document_number} to " . count($userIds) . " users", null, null, ['user_ids' => $userIds], [], $reason);

        return $records;
    }

    /**
     * Démarrer une formation
     */
    public function startTraining(TrainingRecord $record, User $user): void
    {
        if ($record->user_id !== $user->id) {
            throw new \Exception('Vous n\'êtes pas autorisé à démarrer cette formation.');
        }

        if ($record->status === TrainingRecord::STATUS_ASSIGNED) {
            $record->start();
            $this->auditService->log('training_started', 'training', "User started training for document {$record->document->document_number}", $record);
        }
    }

    /**
     * Accuser réception d'une formation (avec signature)
     */
    public function acknowledgeTraining(TrainingRecord $record, User $user, string $pin, ?string $comment = null): void
    {
        if ($record->user_id !== $user->id) {
            throw new \Exception('Vous n\'êtes pas autorisé à valider cette formation.');
        }

        DB::transaction(function () use ($record, $user, $pin, $comment) {
            // Créer la signature électronique
            $signature = $this->signatureService->createSignature(
                $user,
                $record,
                ElectronicSignature::MEANING_ACKNOWLEDGED,
                $pin,
                $comment,
                $record->document,
                $record->documentVersion
            );

            // Mettre à jour le record
            $record->acknowledge($signature->id);

            // Audit
            $this->auditService->log('training_acknowledged', 'training', "User acknowledged training for document {$record->document->document_number}", $record, null, null, [], $comment);
        });
    }

    /**
     * Récupérer les formations en attente d'un utilisateur
     */
    public function getPendingTrainings(User $user): Collection
    {
        return TrainingRecord::where('user_id', $user->id)
            ->whereIn('status', [TrainingRecord::STATUS_ASSIGNED, TrainingRecord::STATUS_IN_PROGRESS])
            ->with(['document', 'documentVersion'])
            ->get();
    }

    /**
     * Obtenir toutes les formations (Admin/Manager)
     */
    public function getAllTrainings(array $filters = []): Collection
    {
        $query = TrainingRecord::with(['user', 'document', 'assignedByUser', 'signature']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['document_id'])) {
            $query->where('document_id', $filters['document_id']);
        }

        return $query->orderBy('assigned_at', 'desc')->get();
    }
}
