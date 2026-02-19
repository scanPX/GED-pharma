<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * GED Training Record Model
 * 
 * Lecture obligatoire et formations documentaires GMP
 */
class TrainingRecord extends Model
{
    protected $table = 'ged_training_records';

    protected $fillable = [
        'uuid',
        'user_id',
        'document_id',
        'document_version_id',
        'status',
        'assigned_at',
        'due_date',
        'started_at',
        'completed_at',
        'acknowledged_at',
        'assigned_by',
        'assignment_reason',
        'time_spent_minutes',
        'quiz_passed',
        'quiz_score',
        'signature_id',
        'exempted_by',
        'exemption_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'time_spent_minutes' => 'integer',
        'quiz_passed' => 'boolean',
        'quiz_score' => 'integer',
    ];

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_EXEMPTED = 'exempted';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($r) => $r->uuid = $r->uuid ?? (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }

    public function exemptedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'exempted_by');
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(ElectronicSignature::class, 'signature_id');
    }

    public function start(): void
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->started_at = now();
        $this->save();
    }

    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
    }

    public function acknowledge(int $signatureId = null): void
    {
        $this->status = self::STATUS_ACKNOWLEDGED;
        $this->acknowledged_at = now();
        $this->signature_id = $signatureId;
        $this->save();
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_ACKNOWLEDGED, self::STATUS_EXEMPTED]);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeOverdue($query)
    {
        return $query->pending()->where('due_date', '<', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
