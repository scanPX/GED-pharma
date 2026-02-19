<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GED Workflow Step Action Model
 * 
 * Enregistre les actions prises sur chaque étape de workflow
 */
class WorkflowStepAction extends Model
{
    protected $table = 'ged_workflow_step_actions';

    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'step_order',
        'user_id',
        'on_behalf_of',
        'action',
        'comment',
        'action_at',
        'signature_required',
        'signature_provided',
        'signature_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'action_at' => 'datetime',
        'signature_required' => 'boolean',
        'signature_provided' => 'boolean',
    ];

    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_REVISION_REQUESTED = 'revision_requested';
    public const ACTION_COMMENTED = 'commented';
    public const ACTION_DELEGATED = 'delegated';
    public const ACTION_ESCALATED = 'escalated';
    public const ACTION_SKIPPED = 'skipped';
    public const ACTION_TIMED_OUT = 'timed_out';

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function onBehalfOfUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'on_behalf_of');
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(ElectronicSignature::class, 'signature_id');
    }

    public function isApproval(): bool
    {
        return $this->action === self::ACTION_APPROVED;
    }

    public function isRejection(): bool
    {
        return $this->action === self::ACTION_REJECTED;
    }
}
