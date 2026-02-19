<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * GED Review Comment Model
 * 
 * Commentaires de revue sur les documents
 */
class ReviewComment extends Model
{
    use SoftDeletes;

    protected $table = 'ged_review_comments';

    protected $fillable = [
        'uuid',
        'document_id',
        'document_version_id',
        'workflow_instance_id',
        'user_id',
        'comment',
        'type',
        'severity',
        'page_number',
        'section_reference',
        'requires_action',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_comment',
        'parent_id',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'requires_action' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public const TYPE_GENERAL = 'general';
    public const TYPE_SUGGESTION = 'suggestion';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_CLARIFICATION = 'clarification';
    public const TYPE_APPROVAL = 'approval';
    public const TYPE_REJECTION = 'rejection';

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MAJOR = 'major';
    public const SEVERITY_CRITICAL = 'critical';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($c) => $c->uuid = $c->uuid ?? (string) Str::uuid());
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ReviewComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReviewComment::class, 'parent_id')->orderBy('created_at');
    }

    public function resolve(int $userId, string $comment = null): void
    {
        $this->is_resolved = true;
        $this->resolved_by = $userId;
        $this->resolved_at = now();
        $this->resolution_comment = $comment;
        $this->save();
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeRequiringAction($query)
    {
        return $query->where('requires_action', true)->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }
}
