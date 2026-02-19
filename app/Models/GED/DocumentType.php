<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GED Document Type Model
 * 
 * Types de documents avec configuration spécifique
 * Ex: SOP Standard, Validation Protocol, Validation Report, Work Instruction
 */
class DocumentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ged_document_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'category_id',
        'allowed_extensions',
        'max_file_size_mb',
        'requires_electronic_signature',
        'numbering_format',
        'review_period_months',
        'is_controlled',
        'is_active',
    ];

    protected $casts = [
        'allowed_extensions' => 'array',
        'max_file_size_mb' => 'integer',
        'requires_electronic_signature' => 'boolean',
        'review_period_months' => 'integer',
        'is_controlled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'type_id');
    }

    public function workflows()
    {
        return $this->belongsToMany(Workflow::class, 'ged_workflow_document_types', 'document_type_id', 'workflow_id')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function getDefaultWorkflow(): ?Workflow
    {
        return $this->workflows()->wherePivot('is_default', true)->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeControlled($query)
    {
        return $query->where('is_controlled', true);
    }
}
