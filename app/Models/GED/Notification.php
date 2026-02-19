<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * GED Notification Model - Système de notifications GED
 */
class Notification extends Model
{
    protected $table = 'ged_notifications';

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'priority',
        'title',
        'message',
        'data',
        'notifiable_type',
        'notifiable_id',
        'action_url',
        'is_read',
        'read_at',
        'is_archived',
        'archived_at',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
    ];

    public const TYPE_DOCUMENT_ASSIGNED = 'document_assigned';
    public const TYPE_APPROVAL_REQUIRED = 'approval_required';
    public const TYPE_APPROVAL_RECEIVED = 'approval_received';
    public const TYPE_DOCUMENT_REJECTED = 'document_rejected';
    public const TYPE_DOCUMENT_APPROVED = 'document_approved';
    public const TYPE_TRAINING_ASSIGNED = 'training_assigned';
    public const TYPE_TRAINING_DUE = 'training_due';
    public const TYPE_TRAINING_OVERDUE = 'training_overdue';
    public const TYPE_REVIEW_REMINDER = 'review_reminder';
    public const TYPE_DOCUMENT_EXPIRING = 'document_expiring';
    public const TYPE_COMMENT_ADDED = 'comment_added';
    public const TYPE_WORKFLOW_COMPLETED = 'workflow_completed';
    public const TYPE_SYSTEM_ALERT = 'system_alert';
    public const TYPE_ACCESS_GRANTED = 'access_granted';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($n) => $n->uuid = $n->uuid ?? (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
    }

    public function archive(): void
    {
        $this->is_archived = true;
        $this->archived_at = now();
        $this->save();
    }

    public static function notify(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $priority = self::PRIORITY_NORMAL,
        ?Model $notifiable = null,
        ?string $actionUrl = null,
        array $data = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'priority' => $priority,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable?->id,
            'action_url' => $actionUrl,
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }
}
