<?php

namespace App\Services\GED;

use App\Models\GED\ElectronicSignature;
use App\Models\GED\Document;
use App\Models\GED\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * GED Electronic Signature Service
 * 
 * Service de signature électronique conforme 21 CFR Part 11
 * Garantit: Authenticité, Intégrité, Non-répudiation
 * 
 * Requirements (21 CFR Part 11 §11.100):
 * - Each signature must be unique to one individual
 * - Identity must be verified before establishment
 * - Signatures must be linked to records
 */
class SignatureService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Créer une signature électronique
     */
    public function createSignature(
        User $user,
        Model $signable,
        string $meaning,
        string $pin,
        ?string $comment = null,
        ?Document $document = null,
        ?DocumentVersion $documentVersion = null
    ): ElectronicSignature {
        // Vérifier que l'utilisateur peut signer
        if (!$user->canSignElectronically()) {
            throw new \Exception('Cet utilisateur n\'est pas autorisé à signer électroniquement.');
        }

        // Vérifier le PIN de signature
        if (!$this->verifySignaturePin($user, $pin)) {
            throw new \Exception('PIN de signature invalide.');
        }

        // Déterminer le document et la version
        if (!$document && method_exists($signable, 'document')) {
            $document = $signable->document;
        }
        if (!$documentVersion && method_exists($signable, 'documentVersion')) {
            $documentVersion = $signable->documentVersion;
        }

        // Calculer le hash du document
        $documentHash = $this->calculateDocumentHash($documentVersion ?? $signable);

        // Générer les données de signature
        $signatureData = $this->generateSignatureData($user, $meaning, $documentHash);

        // Créer la signature
        $signature = ElectronicSignature::create([
            'user_id' => $user->id,
            'document_id' => $document?->id,
            'document_version_id' => $documentVersion?->id,
            'signable_type' => get_class($signable),
            'signable_id' => $signable->id,
            'meaning' => $meaning,
            'meaning_description' => $this->getMeaningDescription($meaning),
            'authentication_method' => 'pin',
            'identity_verified' => true,
            'authenticated_at' => now(),
            'signature_data' => encrypt($signatureData),
            'signature_hash' => hash('sha256', $signatureData),
            'document_hash' => $documentHash,
            'user_full_name' => $user->name,
            'user_title' => $user->title,
            'user_department' => $user->department,
            'signed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'device_info' => $this->getDeviceInfo(),
            'is_valid' => true,
            'reason' => $comment,
            'comment' => $comment,
        ]);

        // Audit log
        $this->auditService->logSignatureApplied($signature, $user);

        return $signature;
    }

    /**
     * Vérifier une signature
     */
    public function verifySignature(ElectronicSignature $signature): array
    {
        $results = [
            'is_valid' => true,
            'checks' => [],
        ];

        // Vérifier si révoquée
        if ($signature->is_revoked) {
            $results['is_valid'] = false;
            $results['checks']['revocation'] = [
                'passed' => false,
                'message' => 'Signature révoquée le ' . $signature->revoked_at->format('d/m/Y H:i'),
            ];
        } else {
            $results['checks']['revocation'] = [
                'passed' => true,
                'message' => 'Non révoquée',
            ];
        }

        // Vérifier le hash de signature
        $hashValid = $signature->verify();
        $results['checks']['hash_integrity'] = [
            'passed' => $hashValid,
            'message' => $hashValid ? 'Hash valide' : 'Hash invalide - intégrité compromise',
        ];
        if (!$hashValid) {
            $results['is_valid'] = false;
        }

        // Vérifier l'utilisateur existe toujours
        $userValid = $signature->user()->exists();
        $results['checks']['user_validity'] = [
            'passed' => $userValid,
            'message' => $userValid ? 'Utilisateur valide' : 'Utilisateur non trouvé',
        ];

        // Vérifier le document si applicable
        if ($signature->document_version_id) {
            $version = $signature->documentVersion;
            if ($version) {
                $currentHash = $this->calculateDocumentHash($version);
                $documentIntact = $currentHash === $signature->document_hash;
                $results['checks']['document_integrity'] = [
                    'passed' => $documentIntact,
                    'message' => $documentIntact ? 'Document non modifié' : 'Document modifié depuis la signature',
                ];
                if (!$documentIntact) {
                    $results['is_valid'] = false;
                }
            }
        }

        return $results;
    }

    /**
     * Révoquer une signature
     */
    public function revokeSignature(ElectronicSignature $signature, User $revokedBy, string $reason): void
    {
        if ($signature->is_revoked) {
            throw new \Exception('Cette signature est déjà révoquée.');
        }

        $signature->revoke($revokedBy->id, $reason);

        $this->auditService->logSignatureRevoked($signature, $revokedBy, $reason);
    }

    /**
     * Configurer le PIN de signature pour un utilisateur
     */
    public function setupSignaturePin(User $user, string $pin, string $passwordConfirmation): void
    {
        // Vérifier le mot de passe de l'utilisateur
        if (!Hash::check($passwordConfirmation, $user->password)) {
            throw new \Exception('Mot de passe incorrect.');
        }

        // Valider le PIN
        if (strlen($pin) < 6) {
            throw new \Exception('Le PIN doit contenir au moins 6 caractères.');
        }

        // Hasher et stocker
        $user->signature_pin_hash = Hash::make($pin);
        $user->can_sign_electronically = true;
        $user->save();
    }

    /**
     * Changer le PIN de signature
     */
    public function changeSignaturePin(User $user, string $currentPin, string $newPin): void
    {
        if (!$this->verifySignaturePin($user, $currentPin)) {
            throw new \Exception('PIN actuel incorrect.');
        }

        if (strlen($newPin) < 6) {
            throw new \Exception('Le nouveau PIN doit contenir au moins 6 caractères.');
        }

        $user->signature_pin_hash = Hash::make($newPin);
        $user->save();
    }

    /**
     * Obtenir l'historique des signatures d'un document
     */
    public function getDocumentSignatures(Document $document): \Illuminate\Database\Eloquent\Collection
    {
        return ElectronicSignature::forDocument($document->id)
            ->with('user')
            ->orderBy('signed_at')
            ->get();
    }

    /**
     * Obtenir les signatures d'un utilisateur
     */
    public function getUserSignatures(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return ElectronicSignature::byUser($user->id)
            ->with(['document', 'documentVersion'])
            ->orderBy('signed_at', 'desc')
            ->get();
    }

    // ========== MÉTHODES PRIVÉES ==========

    /**
     * Vérifier le PIN de signature
     */
    protected function verifySignaturePin(User $user, string $pin): bool
    {
        if (!$user->signature_pin_hash) {
            return false;
        }

        return Hash::check($pin, $user->signature_pin_hash);
    }

    /**
     * Calculer le hash du document signé
     */
    protected function calculateDocumentHash(Model $model): string
    {
        if ($model instanceof DocumentVersion) {
            return $model->file_hash;
        }

        // Pour les autres modèles, hasher leurs attributs clés
        return hash('sha256', json_encode([
            'id' => $model->id,
            'type' => get_class($model),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ]));
    }

    /**
     * Générer les données de signature
     */
    protected function generateSignatureData(User $user, string $meaning, string $documentHash): string
    {
        return json_encode([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'meaning' => $meaning,
            'document_hash' => $documentHash,
            'timestamp' => now()->toIso8601String(),
            'nonce' => bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * Obtenir les informations sur l'appareil
     */
    protected function getDeviceInfo(): array
    {
        $userAgent = request()->userAgent();
        
        return [
            'user_agent' => $userAgent,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtenir la description de la signification
     */
    protected function getMeaningDescription(string $meaning): string
    {
        return match ($meaning) {
            ElectronicSignature::MEANING_CREATED => 'J\'atteste avoir créé ce document',
            ElectronicSignature::MEANING_REVIEWED => 'J\'atteste avoir revu ce document',
            ElectronicSignature::MEANING_VERIFIED => 'J\'atteste avoir vérifié ce document',
            ElectronicSignature::MEANING_APPROVED => 'J\'approuve ce document',
            ElectronicSignature::MEANING_AUTHORIZED => 'J\'autorise ce document',
            ElectronicSignature::MEANING_RELEASED => 'Je libère ce document',
            ElectronicSignature::MEANING_ACKNOWLEDGED => 'J\'ai pris connaissance de ce document',
            ElectronicSignature::MEANING_WITNESSED => 'J\'atteste avoir été témoin de cette action',
            default => $meaning,
        };
    }
}
