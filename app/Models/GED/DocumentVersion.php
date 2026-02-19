<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * GED Document Version Model
 * 
 * Gestion du versioning documentaire avec historique complet
 * Conformité: GMP - Traçabilité des modifications
 * 
 * @property int $id
 * @property string $uuid
 * @property int $document_id
 * @property string $version_number
 * @property int $major_version
 * @property int $minor_version
 * @property string $file_path
 * @property string $file_name
 * @property string $file_extension
 * @property int $file_size
 * @property string $mime_type
 * @property string $file_hash
 * @property int $created_by
 * @property int $status_id
 * @property string|null $change_summary
 * @property string|null $change_justification
 * @property string $change_type
 * @property bool $is_approved
 * @property bool $is_current
 * @property bool $is_effective
 * @property bool $is_draft
 * @property bool $is_obsolete
 */
class DocumentVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ged_document_versions';

    protected $fillable = [
        'uuid',
        'document_id',
        'version_number',
        'major_version',
        'minor_version',
        'file_path',
        'file_name',
        'file_extension',
        'file_size',
        'mime_type',
        'file_hash',
        'created_by',
        'status_id',
        'change_summary',
        'change_justification',
        'change_type',
        'is_approved',
        'approved_at',
        'approved_by',
        'is_current',
        'is_effective',
        'is_draft',
        'is_obsolete',
    ];

    protected $casts = [
        'major_version' => 'integer',
        'minor_version' => 'integer',
        'file_size' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'is_current' => 'boolean',
        'is_effective' => 'boolean',
        'is_draft' => 'boolean',
        'is_obsolete' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($version) {
            if (empty($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
        });
    }

    // ========== RELATIONS ==========

    /**
     * Document parent
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Créateur de la version
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Approbateur
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Statut de la version
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'status_id');
    }

    /**
     * Signatures électroniques sur cette version
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(ElectronicSignature::class, 'document_version_id');
    }

    /**
     * Commentaires de revue
     */
    public function reviewComments(): HasMany
    {
        return $this->hasMany(ReviewComment::class, 'document_version_id');
    }

    /**
     * Actions de workflow sur cette version
     */
    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'document_version_id');
    }

    /**
     * Enregistrements de formation
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class, 'document_version_id');
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Obtenir le chemin complet du fichier
     */
    public function getFullPathAttribute(): string
    {
        // Files are stored on the 'private' disk (storage/app/private)
        return \Illuminate\Support\Facades\Storage::disk('private')->path($this->file_path);
    }

    /**
     * Taille du fichier formatée
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Vérifie l'intégrité du fichier
     */
    public function verifyIntegrity(): bool
    {
        if (!file_exists($this->full_path)) {
            return false;
        }
        
        return hash_file('sha256', $this->full_path) === $this->file_hash;
    }

    /**
     * Calcule et met à jour le hash du fichier
     */
    public function calculateFileHash(): string
    {
        $this->file_hash = hash_file('sha256', $this->full_path);
        return $this->file_hash;
    }

    /**
     * Marquer comme version courante
     */
    public function markAsCurrent(): void
    {
        // Retirer le flag des autres versions
        self::where('document_id', $this->document_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);
        
        $this->is_current = true;
        $this->save();
        
        // Mettre à jour le document parent
        $this->document->update(['current_version_id' => $this->id]);
    }

    /**
     * Marquer comme version effective
     */
    public function markAsEffective(): void
    {
        // Retirer le flag des autres versions
        self::where('document_id', $this->document_id)
            ->where('id', '!=', $this->id)
            ->update(['is_effective' => false, 'is_obsolete' => true]);
        
        $this->is_effective = true;
        $this->is_draft = false;
        $this->save();
    }

    /**
     * Marquer comme obsolète
     */
    public function markAsObsolete(): void
    {
        $this->is_obsolete = true;
        $this->is_effective = false;
        $this->save();
    }

    // ========== SCOPES ==========

    /**
     * Scope: Version courante
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope: Version effective
     */
    public function scopeEffective($query)
    {
        return $query->where('is_effective', true);
    }

    /**
     * Scope: Brouillons
     */
    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    /**
     * Scope: Versions approuvées
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope: Versions non obsolètes
     */
    public function scopeNotObsolete($query)
    {
        return $query->where('is_obsolete', false);
    }
}
