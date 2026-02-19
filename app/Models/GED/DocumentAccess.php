<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GED Document Access Model - Contrôle d'accès au niveau document
 */
class DocumentAccess extends Model
{
    protected $table = 'ged_document_access';

    protected $fillable = [
        'document_id',
        'user_id',
        'role_id',
        'access_level',
        'granted_by',
        'granted_at',
        'expires_at',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public const LEVEL_READ = 'read';
    public const LEVEL_WRITE = 'write';
    public const LEVEL_APPROVE = 'approve';
    public const LEVEL_MANAGE = 'manage';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'granted_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }
}
