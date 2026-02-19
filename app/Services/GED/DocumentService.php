<?php

namespace App\Services\GED;

use App\Models\GED\Document;
use App\Models\GED\DocumentVersion;
use App\Models\GED\DocumentStatus;
use App\Models\GED\DocumentType;
use App\Models\GED\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * GED Document Service
 * 
 * Service principal pour la gestion documentaire GMP
 * Gère le cycle de vie, versioning et traçabilité
 */
class DocumentService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Créer un nouveau document avec sa première version
     */
    public function createDocument(array $data, UploadedFile $file, User $user): Document
    {
        return DB::transaction(function () use ($data, $file, $user) {
            // Générer le numéro de document
            $documentNumber = $this->generateDocumentNumber($data['type_id']);
            
            // Créer le document
            $document = Document::create([
                'document_number' => $documentNumber,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'type_id' => $data['type_id'],
                'status_id' => $this->getDraftStatus()->id,
                'owner_id' => $user->id,
                'author_id' => $user->id,
                'current_version' => '1.0',
                'major_version' => 1,
                'minor_version' => 0,
                'confidentiality' => $data['confidentiality'] ?? 'internal',
                'is_gmp_critical' => $data['is_gmp_critical'] ?? false,
                'is_controlled' => $data['is_controlled'] ?? true,
                'requires_training' => $data['requires_training'] ?? false,
                'language' => $data['language'] ?? 'fr',
                // Ensure department is a string (name) and not a model object to avoid SQL truncation
                'department' => isset($data['department']) ? mb_substr($data['department'], 0, 100) : (isset($user->department) ? mb_substr(is_object($user->department) ? ($user->department->name ?? (string)$user->department) : (string)$user->department, 0, 100) : null),
                'process_area' => $data['process_area'] ?? null,
                'equipment_id' => $data['equipment_id'] ?? null,
                'keywords' => $data['keywords'] ?? null,
                'regulatory_references' => $data['regulatory_references'] ?? null,
            ]);

            // Créer la première version
            $changeSummary = 'Création initiale du document';
            // Truncate to 255 chars to avoid SQL error
            $changeSummary = mb_substr($changeSummary, 0, 255);
            $version = $this->createVersion($document, $file, $user, [
                'change_summary' => $changeSummary,
                'change_type' => 'major',
            ]);

            // Mettre à jour le document avec la version courante
            $document->update(['current_version_id' => $version->id]);

            // Audit log
            $this->auditService->logDocumentCreated($document, $user);

            return $document->fresh(['category', 'type', 'status', 'currentVersionRelation']);
        });
    }

    /**
     * Créer une nouvelle version du document
     */
    public function createVersion(
        Document $document, 
        UploadedFile $file, 
        User $user, 
        array $options = []
    ): DocumentVersion {
        // Stocker le fichier de manière sécurisée
        $storagePath = $this->storeFile($file, $document);
        
        // Calculer le hash SHA-256 pour l'intégrité
        $fileHash = hash_file('sha256', $file->getRealPath());

        $changeType = $options['change_type'] ?? 'minor';
        
        // Calculer le nouveau numéro de version
        if ($changeType === 'major') {
            $majorVersion = $document->major_version + 1;
            $minorVersion = 0;
        } else {
            $majorVersion = $document->major_version;
            $minorVersion = $document->minor_version + 1;
        }
        $versionNumber = "{$majorVersion}.{$minorVersion}";

        // Marquer les versions précédentes comme non courantes
        DocumentVersion::where('document_id', $document->id)
            ->update(['is_current' => false]);

        // Créer la nouvelle version
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $versionNumber,
            'major_version' => $majorVersion,
            'minor_version' => $minorVersion,
            'file_path' => $storagePath,
            'file_name' => $file->getClientOriginalName(),
            'file_extension' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_hash' => $fileHash,
            'created_by' => $user->id,
            'status_id' => $this->getDraftStatus()->id,
            'change_summary' => $options['change_summary'] ?? null,
            'change_justification' => $options['change_justification'] ?? null,
            'change_type' => $changeType,
            'is_current' => true,
            'is_draft' => true,
        ]);

        // Mettre à jour le document
        $document->update([
            'current_version' => $versionNumber,
            'major_version' => $majorVersion,
            'minor_version' => $minorVersion,
            'current_version_id' => $version->id,
        ]);

        // Audit log
        $this->auditService->logVersionCreated($version, $user);

        return $version;
    }

    /**
     * Mettre à jour les métadonnées d'un document
     */
    public function updateDocument(Document $document, array $data, User $user): Document
    {
        $oldValues = $document->only(array_keys($data));

        $document->update($data);

        // Audit log avec les changements
        $this->auditService->logDocumentUpdated($document, $oldValues, $data, $user);

        return $document->fresh();
    }

    /**
     * Changer le statut d'un document
     */
    public function changeStatus(Document $document, string $statusCode, User $user, ?string $comment = null): Document
    {
        $oldStatus = $document->status;
        $newStatus = DocumentStatus::where('code', $statusCode)->firstOrFail();

        $document->status_id = $newStatus->id;
        
        // Mettre à jour les dates selon le statut
        if ($statusCode === DocumentStatus::EFFECTIVE) {
            $document->effective_date = now();
            
            // Calculer la prochaine date de revue
            $reviewMonths = $document->type->review_period_months ?? 24;
            $document->review_date = now()->addMonths($reviewMonths);
        }
        
        if ($statusCode === DocumentStatus::OBSOLETE) {
            $document->expiry_date = now();
        }

        $document->save();

        // Mettre à jour le statut de la version courante
        if ($document->currentVersionRelation) {
            $document->currentVersionRelation->update(['status_id' => $newStatus->id]);
            
            if ($statusCode === DocumentStatus::EFFECTIVE) {
                $document->currentVersionRelation->markAsEffective();
            }
            
            if ($statusCode === DocumentStatus::OBSOLETE) {
                $document->currentVersionRelation->markAsObsolete();
            }
        }

        // Audit log
        $this->auditService->logStatusChange($document, $oldStatus, $newStatus, $user, $comment);

        return $document->fresh(['status']);
    }

    /**
     * Supprimer un document (Admin)
     */
    public function deleteDocument(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            // Supprimer les fichiers des versions
            foreach ($document->versions as $version) {
                if ($version->file_path && Storage::disk('private')->exists($version->file_path)) {
                    Storage::disk('private')->delete($version->file_path);
                }
            }

            // Audit log avant suppression
            $this->auditService->logDocumentDeleted($document, $user);

            // Suppression du document (cascade en DB gérera les versions, instances, etc. si configuré, sinon le faire manuellement)
            // Dans notre cas, on va faire une suppression logique ou physique selon la politique. 
            // Pour la conformité GxP, on préfère souvent l'archivage, mais si on demande 'Supprimer', on le fait.
            $document->delete();
        });
    }

    /**
     * Télécharger un document (avec audit)
     */
    public function downloadDocument(DocumentVersion $version, User $user): string
    {
        // Vérifier l'intégrité avant téléchargement
        if (!$version->verifyIntegrity()) {
            throw new \Exception('Erreur d\'intégrité du fichier détectée.');
        }

        // Audit log
        $this->auditService->logDocumentDownloaded($version, $user);

        return $version->full_path;
    }

    /**
     * Voir un document (avec audit spécifique viewer)
     */
    public function viewDocumentContent(DocumentVersion $version, User $user): string
    {
        // Vérifier l'intégrité avant affichage
        if (!$version->verifyIntegrity()) {
            throw new \Exception('Erreur d\'intégrité du fichier détectée.');
        }

        // Audit log spécifique consultation détaillée
        $this->auditService->logDocumentViewed($version->document, $user);

        return $version->full_path;
    }

    /**
     * Consulter un document (avec audit)
     */
    public function viewDocument(Document $document, User $user): void
    {
        // Enregistrer la consultation
        $document->views()->create([
            'user_id' => $user->id,
            'document_version_id' => $document->current_version_id,
            'viewed_at' => now(),
            'ip_address' => request()->ip(),
        ]);

        $this->auditService->logDocumentViewed($document, $user);
    }

    /**
     * Recherche avancée de documents
     */
    public function searchDocuments(array $filters, User $user): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Document::with(['category', 'type', 'status', 'owner'])
            ->active();

        // Filtre par terme de recherche
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtre par catégorie
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtre par type
        if (!empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        // Filtre par statut
        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        // Filtre par département
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        // Filtre par propriétaire
        if (!empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        // Filtre GMP critical
        if (isset($filters['is_gmp_critical'])) {
            $query->where('is_gmp_critical', $filters['is_gmp_critical']);
        }

        // Filtre par date d'effet
        if (!empty($filters['effective_from'])) {
            $query->where('effective_date', '>=', $filters['effective_from']);
        }
        if (!empty($filters['effective_to'])) {
            $query->where('effective_date', '<=', $filters['effective_to']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'updated_at';
        $sortDir = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Obtenir les documents nécessitant une révision
     */
    public function getDocumentsNeedingReview(): \Illuminate\Database\Eloquent\Collection
    {
        return Document::needingReview()
            ->active()
            ->with(['category', 'type', 'status', 'owner'])
            ->orderBy('review_date')
            ->get();
    }

    // ========== MÉTHODES PRIVÉES ==========

    /**
     * Générer un numéro de document unique
     */
    protected function generateDocumentNumber(int $typeId): string
    {
        $type = DocumentType::findOrFail($typeId);
        $format = $type->numbering_format ?? '{CODE}-{YEAR}-{SEQ:4}';
        
        $year = date('Y');
        $sequence = Document::where('type_id', $typeId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        $number = str_replace(
            ['{CODE}', '{YEAR}', '{SEQ:4}', '{SEQ:5}', '{SEQ:6}'],
            [$type->code, $year, sprintf('%04d', $sequence), sprintf('%05d', $sequence), sprintf('%06d', $sequence)],
            $format
        );

        return $number;
    }

    /**
     * Stocker le fichier de manière sécurisée
     */
    protected function storeFile(UploadedFile $file, Document $document): string
    {
        $directory = 'ged/documents/' . $document->id;
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        return $file->storeAs($directory, $filename, 'private');
    }

    /**
     * Obtenir le statut brouillon
     */
    protected function getDraftStatus(): DocumentStatus
    {
        return DocumentStatus::where('code', DocumentStatus::DRAFT)->firstOrFail();
    }
}
