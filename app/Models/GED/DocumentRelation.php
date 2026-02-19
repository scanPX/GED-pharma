<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GED Document Relation Model - Relations entre documents
 */
class DocumentRelation extends Model
{
    protected $table = 'ged_document_relations';

    protected $fillable = [
        'source_document_id',
        'target_document_id',
        'relation_type',
        'description',
        'created_by',
    ];

    public const TYPE_REFERENCES = 'references';
    public const TYPE_SUPERSEDES = 'supersedes';
    public const TYPE_IS_SUPERSEDED_BY = 'is_superseded_by';
    public const TYPE_RELATED_TO = 'related_to';
    public const TYPE_DERIVED_FROM = 'derived_from';
    public const TYPE_ANNEXE_OF = 'annexe_of';

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    public function targetDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'target_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getRelationLabelAttribute(): string
    {
        return match ($this->relation_type) {
            self::TYPE_REFERENCES => 'Référence',
            self::TYPE_SUPERSEDES => 'Remplace',
            self::TYPE_IS_SUPERSEDED_BY => 'Remplacé par',
            self::TYPE_RELATED_TO => 'Lié à',
            self::TYPE_DERIVED_FROM => 'Dérivé de',
            self::TYPE_ANNEXE_OF => 'Annexe de',
            default => $this->relation_type,
        };
    }
}
