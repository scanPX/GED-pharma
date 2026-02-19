<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * GED Permission Model
 * 
 * Permissions granulaires pour le contrôle d'accès
 * Conformité: GMP Annex 11, 21 CFR Part 11 §11.10
 * 
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $module
 * @property string $action
 * @property string|null $description
 * @property bool $requires_signature
 * @property bool $is_auditable
 */
class Permission extends Model
{
    use HasFactory;

    protected $table = 'ged_permissions';

    protected $fillable = [
        'name',
        'display_name',
        'module',
        'action',
        'description',
        'requires_signature',
        'is_auditable',
    ];

    protected $casts = [
        'requires_signature' => 'boolean',
        'is_auditable' => 'boolean',
    ];

    /**
     * Rôles ayant cette permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'ged_role_permission', 'permission_id', 'role_id')
            ->withPivot(['granted_by', 'granted_at'])
            ->withTimestamps();
    }

    /**
     * Scope: Permissions par module
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Permissions nécessitant signature
     */
    public function scopeRequiresSignature($query)
    {
        return $query->where('requires_signature', true);
    }

    /**
     * Scope: Permissions auditables
     */
    public function scopeAuditable($query)
    {
        return $query->where('is_auditable', true);
    }

    /**
     * Génère le nom de permission standardisé
     */
    public static function generateName(string $module, string $action): string
    {
        return strtolower($module) . '.' . strtolower($action);
    }
}
