<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GED Document Category Model
 * 
 * Classification documentaire pharmaceutique conforme GMP
 * Catégories: SOP, Validation Reports, Specifications, Batch Records, etc.
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $sort_order
 * @property string|null $prefix
 * @property string|null $default_workflow
 * @property int $retention_years
 * @property bool $requires_training
 * @property bool $is_gmp_critical
 * @property bool $is_active
 */
class DocumentCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ged_document_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_id',
        'sort_order',
        'prefix',
        'default_workflow',
        'retention_years',
        'requires_training',
        'is_gmp_critical',
        'is_active',
    ];

    protected $casts = [
        'retention_years' => 'integer',
        'sort_order' => 'integer',
        'requires_training' => 'boolean',
        'is_gmp_critical' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Catégorie parente
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'parent_id');
    }

    /**
     * Sous-catégories
     */
    public function children(): HasMany
    {
        return $this->hasMany(DocumentCategory::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Types de documents dans cette catégorie
     */
    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class, 'category_id');
    }

    /**
     * Documents de cette catégorie
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    /**
     * Chemin complet de la catégorie (breadcrumb)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Scope: Catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Catégories racines (sans parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Catégories GMP critiques
     */
    public function scopeGmpCritical($query)
    {
        return $query->where('is_gmp_critical', true);
    }
}
