<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * GED Electronic Signature Model
 * 
 * Signature électronique conforme 21 CFR Part 11
 * Garantit: Authenticité, Intégrité, Non-répudiation
 * 
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $document_id
 * @property int|null $document_version_id
 * @property string $signable_type
 * @property int $signable_id
 * @property string $meaning
 * @property string $authentication_method
 * @property bool $identity_verified
 * @property string $signature_data
 * @property string $signature_hash
 * @property string $document_hash
 * @property string $user_full_name
 * @property \Carbon\Carbon $signed_at
 * @property string $ip_address
 * @property bool $is_valid
 * @property bool $is_revoked
 */
class ElectronicSignature extends Model
{
    use HasFactory;

    protected $table = 'ged_electronic_signatures';

    protected $fillable = [
        'uuid',
        'user_id',
        'document_id',
        'document_version_id',
        'signable_type',
        'signable_id',
        'meaning',
        'meaning_description',
        'authentication_method',
        'identity_verified',
        'authenticated_at',
        'signature_data',
        'signature_hash',
        'document_hash',
        'user_full_name',
        'user_title',
        'user_department',
        'signed_at',
        'timestamp_token',
        'timestamp_verified',
        'ip_address',
        'user_agent',
        'session_id',
        'device_info',
        'is_valid',
        'is_revoked',
        'revoked_at',
        'revocation_reason',
        'revoked_by',
        'reason',
        'comment',
    ];

    protected $casts = [
        'identity_verified' => 'boolean',
        'authenticated_at' => 'datetime',
        'signed_at' => 'datetime',
        'timestamp_verified' => 'boolean',
        'device_info' => 'array',
        'is_valid' => 'boolean',
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    // Significations de signature (21 CFR Part 11)
    public const MEANING_CREATED = 'created';
    public const MEANING_REVIEWED = 'reviewed';
    public const MEANING_VERIFIED = 'verified';
    public const MEANING_APPROVED = 'approved';
    public const MEANING_AUTHORIZED = 'authorized';
    public const MEANING_RELEASED = 'released';
    public const MEANING_ACKNOWLEDGED = 'acknowledged';
    public const MEANING_WITNESSED = 'witnessed';

    // Méthodes d'authentification
    public const AUTH_PASSWORD = 'password';
    public const AUTH_2FA = '2fa';
    public const AUTH_BIOMETRIC = 'biometric';
    public const AUTH_CERTIFICATE = 'certificate';
    public const AUTH_PIN = 'pin';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($signature) {
            if (empty($signature->uuid)) {
                $signature->uuid = (string) Str::uuid();
            }
            if (empty($signature->signed_at)) {
                $signature->signed_at = now();
            }
        });
    }

    // ========== RELATIONS ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'revoked_by');
    }

    /**
     * Entité signée (polymorphique)
     */
    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Vérifie la validité de la signature
     */
    public function verify(): bool
    {
        if ($this->is_revoked) {
            return false;
        }

        // Vérifier l'intégrité du hash
        $expectedHash = $this->generateSignatureHash();
        
        return $this->signature_hash === $expectedHash && $this->is_valid;
    }

    /**
     * Génère le hash de signature
     */
    public function generateSignatureHash(): string
    {
        $data = implode('|', [
            $this->user_id,
            $this->document_hash,
            $this->meaning,
            $this->signed_at->toIso8601String(),
            $this->user_full_name,
        ]);
        
        return hash('sha256', $data);
    }

    /**
     * Révoque la signature
     */
    public function revoke(int $revokedBy, string $reason): void
    {
        $this->is_revoked = true;
        $this->is_valid = false;
        $this->revoked_at = now();
        $this->revoked_by = $revokedBy;
        $this->revocation_reason = $reason;
        $this->save();
    }

    /**
     * Obtient le libellé de la signification
     */
    public function getMeaningLabelAttribute(): string
    {
        return match ($this->meaning) {
            self::MEANING_CREATED => 'Créé par',
            self::MEANING_REVIEWED => 'Revu par',
            self::MEANING_VERIFIED => 'Vérifié par',
            self::MEANING_APPROVED => 'Approuvé par',
            self::MEANING_AUTHORIZED => 'Autorisé par',
            self::MEANING_RELEASED => 'Libéré par',
            self::MEANING_ACKNOWLEDGED => 'Pris connaissance par',
            self::MEANING_WITNESSED => 'Témoin',
            default => $this->meaning,
        };
    }

    /**
     * Obtient les informations formatées pour l'affichage
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'signer' => $this->user_full_name,
            'title' => $this->user_title,
            'department' => $this->user_department,
            'meaning' => $this->meaning_label,
            'signed_at' => $this->signed_at->format('d/m/Y H:i:s'),
            'is_valid' => $this->is_valid && !$this->is_revoked,
            'authentication' => $this->authentication_method,
        ];
    }

    // ========== SCOPES ==========

    public function scopeValid($query)
    {
        return $query->where('is_valid', true)->where('is_revoked', false);
    }

    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeWithMeaning($query, string $meaning)
    {
        return $query->where('meaning', $meaning);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
