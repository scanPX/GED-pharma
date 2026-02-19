<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * GED Document Model
 * 
 * Modèle principal du document pharmaceutique
 * Conformité: GMP Annex 11, 21 CFR Part 11, ISO 13485
 * 
 * @property int $id
 * @property string $uuid
 * @property string $document_number
 * @property string $title
 * @property string|null $description
 * @property int $category_id
 * @property int $type_id
 * @property int $status_id
 * @property int $owner_id
 * @property int $author_id
 * @property string $current_version
 * @property int $major_version
 * @property int $minor_version
 * @property int|null $current_version_id
 * @property \Carbon\Carbon|null $effective_date
 * @property \Carbon\Carbon|null $review_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property string $confidentiality
 * @property bool $is_gmp_critical
 * @property bool $is_controlled
 * @property bool $requires_training
 * @property string $language
 * @property string|null $department
 * @property string|null $process_area
 * @property string|null $equipment_id
 * @property array|null $keywords
 * @property array|null $regulatory_references
 * @property bool $is_archived
 */
class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ged_documents';

    protected $fillable = [
        'uuid',
        'document_number',
        'title',
        'description',
        'category_id',
        'type_id',
        'status_id',
        'owner_id',
        'author_id',
        'current_version',
        'major_version',
        'minor_version',
        'current_version_id',
        'effective_date',
        'review_date',
        'expiry_date',
        'last_reviewed_at',
        'confidentiality',
        'is_gmp_critical',
        'is_controlled',
        'requires_training',
        'language',
        'department',
        'process_area',
        'equipment_id',
        'keywords',
        'regulatory_references',
        'is_archived',
        'archived_at',
        'archived_by',
        'archive_reason',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'review_date' => 'date',
        'expiry_date' => 'date',
        'last_reviewed_at' => 'datetime',
        'archived_at' => 'datetime',
        'keywords' => 'array',
        'regulatory_references' => 'array',
        'is_gmp_critical' => 'boolean',
        'is_controlled' => 'boolean',
        'requires_training' => 'boolean',
        'is_archived' => 'boolean',
        'major_version' => 'integer',
        'minor_version' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    // ========== RELATIONS ==========

    /**
     * Catégorie du document
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    /**
     * Type de document
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'type_id');
    }

    /**
     * Statut actuel
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'status_id');
    }

    /**
     * Propriétaire du document
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    /**
     * Auteur original
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'author_id');
    }

    /**
     * Utilisateur ayant archivé
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'archived_by');
    }

    /**
     * Version courante du document
     */
    public function currentVersionRelation(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    /**
     * Toutes les versions du document
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderBy('major_version', 'desc')->orderBy('minor_version', 'desc');
    }

    /**
     * Métadonnées personnalisées
     */
    public function metadata(): HasMany
    {
        return $this->hasMany(DocumentMetadata::class, 'document_id');
    }

    /**
     * Relations avec d'autres documents
     */
    public function relations(): HasMany
    {
        return $this->hasMany(DocumentRelation::class, 'source_document_id');
    }

    /**
     * Documents qui référencent ce document
     */
    public function referencedBy(): HasMany
    {
        return $this->hasMany(DocumentRelation::class, 'target_document_id');
    }

    /**
     * Contrôles d'accès spécifiques
     */
    public function accessControls(): HasMany
    {
        return $this->hasMany(DocumentAccess::class, 'document_id');
    }

    /**
     * Workflows associés
     */
    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'document_id');
    }

    /**
     * Commentaires de revue
     */
    public function reviewComments(): HasMany
    {
        return $this->hasMany(ReviewComment::class, 'document_id');
    }

    /**
     * Signatures électroniques
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(ElectronicSignature::class, 'document_id');
    }

    /**
     * Enregistrements de formation
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class, 'document_id');
    }

    /**
     * Logs d'audit
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'document_id');
    }

    /**
     * Historique des consultations (document views)
     */
    public function views(): HasMany
    {
        return $this->hasMany(DocumentView::class, 'document_id');
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Obtenir la version effective actuelle
     */
    public function getEffectiveVersion(): ?DocumentVersion
    {
        return $this->versions()->where('is_effective', true)->first();
    }

    /**
     * Obtenir le dernier brouillon
     */
    public function getLatestDraft(): ?DocumentVersion
    {
        return $this->versions()->where('is_draft', true)->first();
    }

    /**
     * Vérifier si le document est modifiable
     */
    public function isEditable(): bool
    {
        return $this->status->is_editable && !$this->is_archived;
    }

    /**
     * Vérifier si le document nécessite une revue
     */
    public function needsReview(): bool
    {
        if (!$this->review_date) {
            return false;
        }
        return $this->review_date->isPast() || $this->review_date->diffInDays(now()) <= 30;
    }

    /**
     * Vérifier si le document est expiré
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Créer une nouvelle version
     */
    public function createNewVersion(string $changeType = 'minor'): string
    {
        if ($changeType === 'major') {
            $this->major_version++;
            $this->minor_version = 0;
        } else {
            $this->minor_version++;
        }
        
        $this->current_version = $this->major_version . '.' . $this->minor_version;
        return $this->current_version;
    }

    /**
     * Obtenir le numéro de version formaté
     */
    public function getFormattedVersionAttribute(): string
    {
        return 'v' . $this->current_version;
    }

    /**
     * Workflow actif en cours
     */
    public function getActiveWorkflow(): ?WorkflowInstance
    {
        return $this->workflowInstances()
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();
    }

    // ========== SCOPES ==========

    /**
     * Scope: Documents actifs (non archivés)
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Documents archivés
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Documents GMP critiques
     */
    public function scopeGmpCritical($query)
    {
        return $query->where('is_gmp_critical', true);
    }

    /**
     * Scope: Documents par statut
     */
    public function scopeWithStatus($query, string $statusCode)
    {
        return $query->whereHas('status', fn($q) => $q->where('code', $statusCode));
    }

    /**
     * Scope: Documents par département
     */
    public function scopeForDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope: Documents nécessitant revue
     */
    public function scopeNeedingReview($query)
    {
        return $query->where('review_date', '<=', now()->addDays(30));
    }

    /**
     * Scope: Recherche textuelle
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('document_number', 'like', "%{$term}%")
              ->orWhere('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhereJsonContains('keywords', $term);
        });
    }
}
