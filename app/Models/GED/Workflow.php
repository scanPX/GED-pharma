<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GED Workflow Model
 * 
 * Définition des workflows d'approbation multi-niveaux
 * Conformité: GMP - Processus d'approbation formalisés
 */
class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ged_workflows';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'requires_sequential_approval',
        'allows_parallel_approval',
        'requires_all_approvers',
        'min_approvers',
        'allows_delegation',
        'allows_rejection',
        'allows_revision_request',
        'escalation_days',
        'escalation_role_id',
        'notify_on_submit',
        'notify_on_approve',
        'notify_on_reject',
        'notify_on_complete',
        'is_active',
    ];

    protected $casts = [
        'requires_sequential_approval' => 'boolean',
        'allows_parallel_approval' => 'boolean',
        'requires_all_approvers' => 'boolean',
        'min_approvers' => 'integer',
        'allows_delegation' => 'boolean',
        'allows_rejection' => 'boolean',
        'allows_revision_request' => 'boolean',
        'escalation_days' => 'integer',
        'notify_on_submit' => 'boolean',
        'notify_on_approve' => 'boolean',
        'notify_on_reject' => 'boolean',
        'notify_on_complete' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Types de workflow
    public const TYPE_APPROVAL = 'approval';
    public const TYPE_REVIEW = 'review';
    public const TYPE_VALIDATION = 'validation';
    public const TYPE_CHANGE_CONTROL = 'change_control';

    /**
     * Étapes du workflow
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'workflow_id')->orderBy('step_order');
    }

    /**
     * Rôle d'escalade
     */
    public function escalationRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'escalation_role_id');
    }

    /**
     * Instances actives de ce workflow
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'workflow_id');
    }

    /**
     * Types de documents associés
     */
    public function documentTypes()
    {
        return $this->belongsToMany(DocumentType::class, 'ged_workflow_document_types', 'workflow_id', 'document_type_id')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Obtenir la première étape
     */
    public function getFirstStep(): ?WorkflowStep
    {
        return $this->steps()->orderBy('step_order')->first();
    }

    /**
     * Obtenir l'étape suivante
     */
    public function getNextStep(int $currentOrder): ?WorkflowStep
    {
        return $this->steps()->where('step_order', '>', $currentOrder)->orderBy('step_order')->first();
    }

    /**
     * Obtenir la dernière étape
     */
    public function getLastStep(): ?WorkflowStep
    {
        return $this->steps()->orderBy('step_order', 'desc')->first();
    }

    /**
     * Vérifie si le workflow est complet (toutes les étapes passées)
     */
    public function isCompleteAtStep(int $stepOrder): bool
    {
        $lastStep = $this->getLastStep();
        return $lastStep && $lastStep->step_order <= $stepOrder;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
