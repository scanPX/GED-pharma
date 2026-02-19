<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

/**
 * Department Model - Départements pour GED Pharma
 * 
 * Les départements organisent les utilisateurs par fonction:
 * - Qualité (QA)
 * - Contrôle Qualité (QC)
 * - Production
 * - Réglementaire
 * - R&D
 */
class Department extends Model
{
    use HasFactory;

    protected $table = 'ged_departments';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ========== RELATIONS ==========

    /**
     * Utilisateurs du département
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
