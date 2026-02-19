<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GED Role Model
 * 
 * Gestion des rôles utilisateurs avec permissions granulaires
 * Conformité: GMP Annex 11 - Contrôle d'accès basé sur les rôles
 * 
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string $access_level
 * @property bool $can_approve_documents
 * @property bool $can_sign_electronically
 * @property bool $can_manage_workflows
 * @property bool $can_view_audit_trail
 * @property bool $can_manage_users
 * @property bool $is_system_role
 * @property bool $is_active
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ged_roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'access_level',
        'can_approve_documents',
        'can_sign_electronically',
        'can_manage_workflows',
        'can_view_audit_trail',
        'can_manage_users',
        'is_system_role',
        'is_active',
    ];

    protected $casts = [
        'can_approve_documents' => 'boolean',
        'can_sign_electronically' => 'boolean',
        'can_manage_workflows' => 'boolean',
        'can_view_audit_trail' => 'boolean',
        'can_manage_users' => 'boolean',
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Permissions associées au rôle
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'ged_role_permission', 'role_id', 'permission_id')
            ->withPivot(['granted_by', 'granted_at'])
            ->withTimestamps();
    }

    /**
     * Utilisateurs ayant ce rôle
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'ged_user_roles', 'role_id', 'user_id')
            ->withPivot(['assigned_by', 'assigned_at', 'expires_at', 'assignment_reason', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Vérifie si le rôle possède une permission spécifique
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Vérifie si le rôle possède une des permissions listées
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Scope: Rôles actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Rôles avec droit d'approbation
     */
    public function scopeCanApprove($query)
    {
        return $query->where('can_approve_documents', true);
    }

    /**
     * Scope: Rôles avec droit de signature électronique
     */
    public function scopeCanSign($query)
    {
        return $query->where('can_sign_electronically', true);
    }
}
