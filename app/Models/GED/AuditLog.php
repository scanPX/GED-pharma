<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * GED Audit Log Model
 * 
 * Traçabilité complète et infalsifiable conforme GMP
 * EU Annex 11, 21 CFR Part 11 §11.10(e)
 * 
 * IMPORTANT: Cette table ne doit JAMAIS être modifiée ou supprimée
 * Les enregistrements sont immuables pour garantir l'intégrité de l'audit trail
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'ged_audit_logs';

    // Empêcher les mises à jour - Audit trail immuable
    public $timestamps = true;
    const UPDATED_AT = null; // Pas de updated_at, logs immuables

    protected $fillable = [
        'uuid',
        'user_id',
        'user_name',
        'user_email',
        'user_role_id',
        'user_role_name',
        'action',
        'action_category',
        'action_description',
        'auditable_type',
        'auditable_id',
        'auditable_name',
        'document_id',
        'document_number',
        'document_version',
        'old_values',
        'new_values',
        'changed_fields',
        'metadata',
        'comment',
        'status',
        'failure_reason',
        'occurred_at',
        'timezone',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'request_method',
        'request_url',
        'previous_hash',
        'entry_hash',
        'is_gmp_critical',
        'requires_review',
        'is_security_event',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime:Y-m-d H:i:s.u',
        'is_gmp_critical' => 'boolean',
        'requires_review' => 'boolean',
        'is_security_event' => 'boolean',
    ];

    // Catégories d'actions
    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_WORKFLOW = 'workflow';
    public const CATEGORY_USER = 'user';
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_ACCESS = 'access';
    public const CATEGORY_SIGNATURE = 'signature';
    public const CATEGORY_TRAINING = 'training';

    // Actions principales
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_PRINT = 'print';
    public const ACTION_EXPORT = 'export';
    public const ACTION_APPROVE = 'approve';
    public const ACTION_REJECT = 'reject';
    public const ACTION_SIGN = 'sign';
    public const ACTION_SUBMIT = 'submit';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_LOGIN_FAILED = 'login_failed';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            if (empty($log->occurred_at)) {
                $log->occurred_at = now();
            }
            
            // Calcul du hash de chaînage (intégrité)
            $log->entry_hash = $log->calculateEntryHash();
        });

        // Empêcher toute modification
        static::updating(function ($log) {
            throw new \Exception('Les entrées d\'audit ne peuvent pas être modifiées.');
        });

        // Empêcher toute suppression
        static::deleting(function ($log) {
            throw new \Exception('Les entrées d\'audit ne peuvent pas être supprimées.');
        });
    }

    // ========== RELATIONS ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'user_role_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Entité auditée (polymorphique)
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Calcule le hash de l'entrée pour l'intégrité
     */
    public function calculateEntryHash(): string
    {
        $data = implode('|', [
            $this->uuid,
            $this->user_id ?? 'system',
            $this->action,
            $this->action_category,
            $this->auditable_type ?? '',
            $this->auditable_id ?? '',
            $this->occurred_at?->toIso8601String() ?? now()->toIso8601String(),
            json_encode($this->old_values ?? []),
            json_encode($this->new_values ?? []),
            $this->previous_hash ?? 'genesis',
        ]);
        
        return hash('sha256', $data);
    }

    /**
     * Vérifie l'intégrité de l'entrée
     */
    public function verifyIntegrity(): bool
    {
        return $this->entry_hash === $this->calculateEntryHash();
    }

    /**
     * Vérifie la chaîne d'intégrité depuis cette entrée
     */
    public static function verifyChainIntegrity(int $fromId = 1): array
    {
        $logs = self::where('id', '>=', $fromId)->orderBy('id')->get();
        $errors = [];
        $previousHash = null;

        foreach ($logs as $log) {
            // Vérifier le hash de l'entrée
            if (!$log->verifyIntegrity()) {
                $errors[] = [
                    'id' => $log->id,
                    'error' => 'Hash d\'entrée invalide',
                ];
            }

            // Vérifier le chaînage
            if ($previousHash !== null && $log->previous_hash !== $previousHash) {
                $errors[] = [
                    'id' => $log->id,
                    'error' => 'Rupture de chaîne détectée',
                ];
            }

            $previousHash = $log->entry_hash;
        }

        return $errors;
    }

    /**
     * Créer une entrée d'audit
     */
    public static function log(
        string $action,
        string $category,
        string $description,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?string $comment = null
    ): self {
        $user = auth()->user();
        $request = request();
        
        // Récupérer le dernier hash pour le chaînage
        $lastLog = self::latest('id')->first();
        $previousHash = $lastLog?->entry_hash;

        // Déterminer les champs modifiés safely
        $changedFields = [];
        foreach ($newValues as $key => $newValue) {
            if (!array_key_exists($key, $oldValues) || $oldValues[$key] !== $newValue) {
                $changedFields[] = $key;
            }
        }

        return self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_role_id' => $user?->primaryRole?->id,
            'user_role_name' => $user?->primaryRole?->name,
            'action' => $action,
            'action_category' => $category,
            'action_description' => $description,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'auditable_name' => $auditable?->title ?? $auditable?->name ?? null,
            'document_id' => $auditable instanceof Document ? $auditable->id : ($auditable?->document_id ?? null),
            'document_number' => $auditable instanceof Document ? $auditable->document_number : null,
            'document_version' => $auditable instanceof DocumentVersion ? $auditable->version_number : null,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'changed_fields' => $changedFields ?: null,
            'metadata' => $metadata ?: null,
            'comment' => $comment,
            'status' => 'success',
            'occurred_at' => now(),
            'timezone' => config('app.timezone'),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => $request?->header('X-Request-ID') ?? (string) Str::uuid(),
            'request_method' => $request?->method(),
            'request_url' => $request?->fullUrl(),
            'previous_hash' => $previousHash,
            'is_gmp_critical' => $auditable instanceof Document && $auditable->is_gmp_critical,
            'is_security_event' => in_array($action, [self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_LOGIN_FAILED]),
        ]);
    }

    // ========== SCOPES ==========

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeGmpCritical($query)
    {
        return $query->where('is_gmp_critical', true);
    }

    public function scopeSecurityEvents($query)
    {
        return $query->where('is_security_event', true);
    }

    public function scopeRequiringReview($query)
    {
        return $query->where('requires_review', true);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Recherche textuelle globale
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('action_description', 'like', "%{$term}%")
              ->orWhere('user_name', 'like', "%{$term}%")
              ->orWhere('user_email', 'like', "%{$term}%")
              ->orWhere('auditable_name', 'like', "%{$term}%")
              ->orWhere('document_number', 'like', "%{$term}%")
              ->orWhere('ip_address', 'like', "%{$term}%")
              ->orWhere('comment', 'like', "%{$term}%");
        });
    }
}
