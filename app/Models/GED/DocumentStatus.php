<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GED Document Status Model
 * 
 * Statuts du cycle de vie documentaire GMP
 * États: Draft, In Review, Approved, Effective, Obsolete
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $color
 * @property string|null $icon
 * @property string|null $description
 * @property bool $is_editable
 * @property bool $is_visible_to_all
 * @property bool $requires_signature
 * @property bool $triggers_training
 * @property int $sort_order
 * @property bool $is_active
 */
class DocumentStatus extends Model
{
    use HasFactory;

    protected $table = 'ged_document_statuses';

    protected $fillable = [
        'code',
        'name',
        'color',
        'icon',
        'description',
        'is_editable',
        'is_visible_to_all',
        'requires_signature',
        'triggers_training',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
        'is_visible_to_all' => 'boolean',
        'requires_signature' => 'boolean',
        'triggers_training' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Constantes pour les statuts standards
    public const DRAFT = 'DRAFT';
    public const IN_REVIEW = 'IN_REVIEW';
    public const PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const APPROVED = 'APPROVED';
    public const EFFECTIVE = 'EFFECTIVE';
    public const SUPERSEDED = 'SUPERSEDED';
    public const OBSOLETE = 'OBSOLETE';
    public const ARCHIVED = 'ARCHIVED';

    /**
     * Documents avec ce statut
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'status_id');
    }

    /**
     * Versions de documents avec ce statut
     */
    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'status_id');
    }

    /**
     * Vérifie si le document est modifiable dans ce statut
     */
    public function allowsEditing(): bool
    {
        return $this->is_editable;
    }

    /**
     * Vérifie si la transition vers ce statut nécessite une signature
     */
    public function requiresSignature(): bool
    {
        return $this->requires_signature;
    }

    /**
     * Scope: Statuts actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope: Statuts visibles par tous
     */
    public function scopeVisibleToAll($query)
    {
        return $query->where('is_visible_to_all', true);
    }

    /**
     * Scope: Statuts éditables
     */
    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    /**
     * Obtenir le code couleur CSS approprié
     */
    public function getColorClassAttribute(): string
    {
        return match ($this->code) {
            self::DRAFT => 'bg-gray-100 text-gray-800',
            self::IN_REVIEW => 'bg-yellow-100 text-yellow-800',
            self::PENDING_APPROVAL => 'bg-blue-100 text-blue-800',
            self::APPROVED => 'bg-green-100 text-green-800',
            self::EFFECTIVE => 'bg-emerald-100 text-emerald-800',
            self::SUPERSEDED => 'bg-orange-100 text-orange-800',
            self::OBSOLETE => 'bg-red-100 text-red-800',
            self::ARCHIVED => 'bg-slate-100 text-slate-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
