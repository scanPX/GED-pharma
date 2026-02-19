<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use App\Models\GED\Role;
use App\Models\GED\Document;
use App\Models\GED\ElectronicSignature;
use App\Models\GED\TrainingRecord;
use App\Models\GED\Notification as GedNotification;
use App\Models\GED\AuditLog;
use App\Models\GED\WorkflowInstance;
use App\Models\GED\ReviewComment;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'title',
        'department_id',
        'fonction_id',
        'phone',
        'is_active',
        'can_sign_electronically',
        'signature_pin',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'failed_login_attempts',
        'locked_until',
        'locked_at',
        'timezone',
        'language',
        'training_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'signature_pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'locked_at' => 'datetime',
            'training_completed_at' => 'datetime',
            'is_active' => 'boolean',
            'can_sign_electronically' => 'boolean',
            'failed_login_attempts' => 'integer',
        ];
    }

    // ========== GED RELATIONS ==========

    /**
     * Département de l'utilisateur
     */
    public function department()
    {
        return $this->belongsTo(\App\Models\GED\Departement::class, 'department_id');
    }

    /**
     * Fonction de l'utilisateur
     */
    public function fonction()
    {
        return $this->belongsTo(\App\Models\GED\Fonction::class, 'fonction_id');
    }

    /**
     * Rôles GED de l'utilisateur (alias pour compatibilité)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'ged_user_roles', 'user_id', 'role_id')
            ->withPivot(['assigned_by', 'assigned_at', 'expires_at', 'assignment_reason', 'is_active'])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    /**
     * Rôles GED de l'utilisateur
     */
    public function gedRoles(): BelongsToMany
    {
        return $this->roles();
    }

    /**
     * Rôle principal (premier rôle actif)
     */
    public function getPrimaryRoleAttribute(): ?Role
    {
        return $this->gedRoles()->first();
    }

    /**
     * Documents possédés
     */
    public function ownedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_id');
    }

    /**
     * Documents créés
     */
    public function authoredDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'author_id');
    }

    /**
     * Signatures électroniques
     */
    public function electronicSignatures(): HasMany
    {
        return $this->hasMany(ElectronicSignature::class, 'user_id');
    }

    /**
     * Assigner un rôle à l'utilisateur
     */
    public function assignRole(Role $role, ?int $assignedBy = null, ?string $reason = null): void
    {
        if (!$this->hasRole($role->name)) {
            $this->roles()->attach($role->id, [
                'assigned_by' => $assignedBy ?? auth()->id(),
                'assigned_at' => now(),
                'assignment_reason' => $reason,
                'is_active' => true
            ]);
            $this->load('roles'); // Refresh relation
        }
    }

    /**
     * Formations assignées
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class, 'user_id');
    }

    /**
     * Notifications GED
     */
    public function gedNotifications(): HasMany
    {
        return $this->hasMany(GedNotification::class, 'user_id');
    }

    /**
     * Actions d'audit
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * Workflows initiés
     */
    public function initiatedWorkflows(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'initiated_by');
    }

    /**
     * Commentaires de revue
     */
    public function reviewComments(): HasMany
    {
        return $this->hasMany(ReviewComment::class, 'user_id');
    }

    // ========== GED PERMISSIONS ==========

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleName): bool
    {
        return $this->gedRoles()->where('name', $roleName)->exists();
    }

    /**
     * Vérifie si l'utilisateur a au moins un des rôles spécifiés
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->gedRoles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->gedRoles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur a au moins une des permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($this->gedRoles as $role) {
            if ($role->hasAnyPermission($permissions)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur peut approuver des documents
     */
    public function canApproveDocuments(): bool
    {
        return $this->gedRoles()->where('can_approve_documents', true)->exists();
    }

    /**
     * Vérifie si l'utilisateur peut signer électroniquement
     */
    public function canSignElectronically(): bool
    {
        return $this->can_sign_electronically && 
               $this->gedRoles()->where('can_sign_electronically', true)->exists();
    }

    /**
     * Vérifie si l'utilisateur peut voir l'audit trail
     */
    public function canViewAuditTrail(): bool
    {
        return $this->gedRoles()->where('can_view_audit_trail', true)->exists();
    }

    // ========== ACCOUNT STATUS ==========

    /**
     * Vérifie si le compte est verrouillé
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Verrouille le compte
     */
    public function lock(int $minutes = 30): void
    {
        $this->locked_until = now()->addMinutes($minutes);
        $this->save();
    }

    /**
     * Déverrouille le compte
     */
    public function unlock(): void
    {
        $this->locked_until = null;
        $this->failed_login_attempts = 0;
        $this->save();
    }

    /**
     * Incrémente les tentatives de connexion échouées
     */
    public function incrementFailedLogins(): void
    {
        $this->failed_login_attempts++;
        
        // Verrouiller après 5 tentatives
        if ($this->failed_login_attempts >= 5) {
            $this->lock(30);
        }
        
        $this->save();
    }

    /**
     * Reset les tentatives de connexion
     */
    public function resetFailedLogins(): void
    {
        $this->failed_login_attempts = 0;
        $this->last_login_at = now();
        $this->save();
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole($query, string $roleName)
    {
        return $query->whereHas('gedRoles', fn($q) => $q->where('name', $roleName));
    }

    public function scopeInDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeCanApprove($query)
    {
        return $query->whereHas('gedRoles', fn($q) => $q->where('can_approve_documents', true));
    }
}
