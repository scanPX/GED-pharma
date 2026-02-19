<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GED Workflow Step Model
 * 
 * Étapes individuelles dans un workflow d'approbation
 */
class WorkflowStep extends Model
{
    use HasFactory;

    protected $table = 'ged_workflow_steps';

    protected $fillable = [
        'workflow_id',
        'name',
        'description',
        'step_order',
        'step_type',
        'required_role_id',
        'required_user_id',
        'allowed_roles',
        'any_user_with_permission',
        'requires_comment',
        'requires_signature',
        'timeout_days',
        'can_be_skipped',
        'skip_condition',
        'target_status_id',
        'rejection_status_id',
        'is_active',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'allowed_roles' => 'array',
        'any_user_with_permission' => 'boolean',
        'requires_comment' => 'boolean',
        'requires_signature' => 'boolean',
        'timeout_days' => 'integer',
        'can_be_skipped' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Types d'étapes
    public const TYPE_REVIEW = 'review';
    public const TYPE_APPROVAL = 'approval';
    public const TYPE_SIGNATURE = 'signature';
    public const TYPE_QA_APPROVAL = 'qa_approval';
    public const TYPE_REGULATORY_APPROVAL = 'regulatory_approval';
    public const TYPE_FINAL_APPROVAL = 'final_approval';

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function requiredRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'required_role_id');
    }

    public function requiredUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'required_user_id');
    }

    public function targetStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'target_status_id');
    }

    public function rejectionStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'rejection_status_id');
    }

    /**
     * Vérifie si un utilisateur peut exécuter cette étape
     */
    public function canUserExecute(\App\Models\User $user): bool
    {
        // Utilisateur spécifique requis
        if (!empty($this->required_user_id)) {
            return $user->id === $this->required_user_id;
        }

        // Rôle spécifique requis (vérifier par id pour éviter accès à une relation nulle)
        if (!empty($this->required_role_id)) {
            return $user->gedRoles()->where('ged_roles.id', $this->required_role_id)->exists();
        }

        // Un des rôles autorisés (stored as array of role names)
        if (!empty($this->allowed_roles) && is_array($this->allowed_roles) && count($this->allowed_roles) > 0) {
            return $user->hasAnyRole($this->allowed_roles);
        }

        // Tout utilisateur avec permission
        if (!empty($this->any_user_with_permission)) {
            return $user->hasPermission('workflow.approve');
        }

        return false;
    }

    /**
     * Vérifie si c'est une étape de type approbation
     */
    public function isApprovalStep(): bool
    {
        return in_array($this->step_type, [
            self::TYPE_APPROVAL,
            self::TYPE_QA_APPROVAL,
            self::TYPE_REGULATORY_APPROVAL,
            self::TYPE_FINAL_APPROVAL,
        ]);
    }

    /**
     * Vérifie si une signature électronique est requise
     */
    public function requiresElectronicSignature(): bool
    {
        return $this->requires_signature || $this->step_type === self::TYPE_SIGNATURE;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
