<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * GED Workflow Instance Model
 * 
 * Instance active d'un workflow sur un document
 * Conformité: GMP - Traçabilité des approbations
 */
class WorkflowInstance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ged_workflow_instances';

    protected $fillable = [
        'uuid',
        'workflow_id',
        'document_id',
        'document_version_id',
        'initiated_by',
        'initiated_at',
        'status',
        'current_step_order',
        'current_step_id',
        'submitted_at',
        'completed_at',
        'due_date',
        'final_comment',
        'completed_by',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'datetime',
        'current_step_order' => 'integer',
    ];

    // Statuts possibles
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($instance) {
            if (empty($instance->uuid)) {
                $instance->uuid = (string) Str::uuid();
            }
            if (empty($instance->initiated_at)) {
                $instance->initiated_at = now();
            }
        });
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'initiated_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'completed_by');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowStepAction::class, 'workflow_instance_id')->orderBy('created_at');
    }

    /**
     * Soumettre le workflow pour approbation
     */
    public function submit(): void
    {
        $this->status = self::STATUS_PENDING;
        $this->submitted_at = now();
        
        // Définir la première étape
        $firstStep = $this->workflow->getFirstStep();
        if ($firstStep) {
            $this->current_step_id = $firstStep->id;
            $this->current_step_order = $firstStep->step_order;
        }
        
        $this->save();
    }

    /**
     * Avancer à l'étape suivante
     */
    public function advanceToNextStep(): bool
    {
        $nextStep = $this->workflow->getNextStep($this->current_step_order);
        
        if ($nextStep) {
            $this->current_step_id = $nextStep->id;
            $this->current_step_order = $nextStep->step_order;
            $this->status = self::STATUS_IN_PROGRESS;
            $this->save();
            return true;
        }
        
        // Plus d'étapes = workflow terminé
        $this->complete();
        return false;
    }

    /**
     * Marquer comme complété (approuvé)
     */
    public function complete(?int $completedBy = null): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->completed_at = now();
        $this->completed_by = $completedBy;
        $this->save();
    }

    /**
     * Marquer comme rejeté
     */
    public function reject(string $reason, int $rejectedBy): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->completed_at = now();
        $this->completed_by = $rejectedBy;
        $this->final_comment = $reason;
        $this->save();
    }

    /**
     * Annuler le workflow
     */
    public function cancel(string $reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->completed_at = now();
        $this->final_comment = $reason;
        $this->save();
    }

    /**
     * Vérifie si le workflow est terminé
     */
    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED, self::STATUS_EXPIRED]);
    }

    /**
     * Vérifie si le workflow est actif
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Vérifie si c'est en attente à l'étape courante
     */
    public function isPendingAtCurrentStep(): bool
    {
        return $this->isActive() && $this->current_step_id !== null;
    }

    /**
     * Obtient les approbateurs potentiels pour l'étape courante
     */
    public function getCurrentStepApprovers(): array
    {
        if (!$this->currentStep) {
            return [];
        }

        $step = $this->currentStep;

        if ($step->required_user_id) {
            return User::where('id', $step->required_user_id)->get()->toArray();
        }

        if ($step->required_role_id) {
            return User::whereHas('gedRoles', fn($q) => $q->where('ged_roles.id', $step->required_role_id))->get()->toArray();
        }

        if ($step->allowed_roles && is_array($step->allowed_roles) && count($step->allowed_roles) > 0) {
            return User::whereHas('gedRoles', fn($q) => $q->whereIn('name', $step->allowed_roles))->get()->toArray();
        }

        if ($step->any_user_with_permission) {
            return User::whereHas('gedRoles', fn($q) => $q->whereHas('permissions', fn($p) => $p->where('name', 'workflow.approve')))->get()->toArray();
        }

        return [];
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED, self::STATUS_EXPIRED]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopePendingForUser($query, int $userId)
    {
        // Filtrer les workflows en attente d'action de cet utilisateur
        return $query->active();
    }
}
