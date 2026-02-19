<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GED Document Metadata Model - Métadonnées personnalisées
 */
class DocumentMetadata extends Model
{
    protected $table = 'ged_document_metadata';

    protected $fillable = [
        'document_id',
        'key',
        'value',
        'value_type',
        'is_searchable',
        'is_required',
    ];

    protected $casts = [
        'is_searchable' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function getTypedValueAttribute()
    {
        return match ($this->value_type) {
            'number' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'date' => \Carbon\Carbon::parse($this->value),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}
