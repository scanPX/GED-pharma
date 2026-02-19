<?php

namespace App\Http\Controllers\GED;

use App\Http\Controllers\Controller;
use App\Models\GED\Document;
use App\Models\GED\DocumentCategory;
use App\Models\GED\DocumentType;
use App\Models\GED\DocumentStatus;
use App\Services\GED\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Services\GED\WorkflowService;
use App\Models\GED\AuditLog;

/**
 * GED Document Controller
 * 
 * API RESTful pour la gestion documentaire GMP
 */
class DocumentController extends Controller
{
    protected DocumentService $documentService;
    protected WorkflowService $workflowService;

    public function __construct(DocumentService $documentService, WorkflowService $workflowService)
    {
        $this->documentService = $documentService;
        $this->workflowService = $workflowService;
    }

    /**
     * Liste des documents avec filtres et pagination
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:ged_document_categories,id',
            'type_id' => 'nullable|integer|exists:ged_document_types,id',
            'status_id' => 'nullable|integer|exists:ged_document_statuses,id',
            'department' => 'nullable|string|max:100',
            'owner_id' => 'nullable|integer|exists:users,id',
            'is_gmp_critical' => 'nullable|boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date',
            'sort_by' => 'nullable|string|in:document_number,title,created_at,updated_at,effective_date',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $documents = $this->documentService->searchDocuments($filters, $request->user());

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Créer un nouveau document
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'category_id' => 'required|integer|exists:ged_document_categories,id',
            'type_id' => 'required|integer|exists:ged_document_types,id',
            'file' => 'required|file|max:51200', // 50MB max
            'confidentiality' => 'nullable|string|in:public,internal,confidential,restricted',
            'is_gmp_critical' => 'nullable|boolean',
            'is_controlled' => 'nullable|boolean',
            'requires_training' => 'nullable|boolean',
            'language' => 'nullable|string|size:2',
            'department' => 'nullable|string|max:100',
            'process_area' => 'nullable|string|max:100',
            'equipment_id' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'regulatory_references' => 'nullable|array',
        ]);

        try {
            $document = $this->documentService->createDocument(
                $validated,
                $request->file('file'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document créé avec succès',
                'data' => $document,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Afficher un document
     */
    public function show(Document $document): JsonResponse
    {
        $this->documentService->viewDocument($document, request()->user());

        $document->load([
            'category',
            'type',
            'status',
            'owner',
            'author',
            'currentVersionRelation',
            'versions' => fn($q) => $q->orderBy('major_version', 'desc')->orderBy('minor_version', 'desc'),
            'metadata',
            'relations.targetDocument',
            'workflowInstances' => fn($q) => $q->latest()->limit(5),
            'reviewComments' => fn($q) => $q->unresolved(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $document,
        ]);
    }

    /**
     * Mettre à jour un document
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        if (!$document->isEditable()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document ne peut pas être modifié dans son état actuel.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'confidentiality' => 'nullable|string|in:public,internal,confidential,restricted',
            'is_gmp_critical' => 'nullable|boolean',
            'requires_training' => 'nullable|boolean',
            'department' => 'nullable|string|max:100',
            'process_area' => 'nullable|string|max:100',
            'equipment_id' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'regulatory_references' => 'nullable|array',
        ]);

        try {
            $document = $this->documentService->updateDocument($document, $validated, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Document mis à jour',
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Créer une nouvelle version du document
     */
    public function createVersion(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200',
            'change_type' => 'required|string|in:major,minor,editorial',
            'change_summary' => 'required|string|max:5000',
            'change_justification' => 'nullable|string|max:2000',
        ]);

        try {
            $version = $this->documentService->createVersion(
                $document,
                $request->file('file'),
                $request->user(),
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Nouvelle version créée',
                'data' => $version,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Voir un document dans le navigateur
     */
    public function view(Document $document, ?int $versionId = null): \Symfony\Component\HttpFoundation\Response
    {
        $version = $versionId 
            ? $document->versions()->findOrFail($versionId)
            : $document->currentVersionRelation;

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Version non trouvée',
            ], 404);
        }

        try {
            // This performs integrity checks and audit logging
            $this->documentService->viewDocumentContent($version, request()->user());

            return \Illuminate\Support\Facades\Storage::disk('private')->response($version->file_path, $version->file_name, [
                'Content-Type' => $version->mime_type,
                'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $version->file_name) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Télécharger un document
     */
    public function download(Document $document, ?int $versionId = null): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $version = $versionId 
            ? $document->versions()->findOrFail($versionId)
            : $document->currentVersionRelation;

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Version non trouvée',
            ], 404);
        }

        try {
            $path = $this->documentService->downloadDocument($version, request()->user());
            
            return response()->download($path, $version->file_name, [
                'Content-Type' => $version->mime_type,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Archiver un document
     */
    public function archive(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $document = $this->documentService->archiveDocument(
                $document,
                $request->user(),
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Document archivé',
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Liste des catégories
     */
    public function categories(): JsonResponse
    {
        $categories = DocumentCategory::active()
            ->with('children')
            ->root()
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Liste des types de documents
     */
    public function types(Request $request): JsonResponse
    {
        $query = DocumentType::active()->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * Liste des statuts
     */
    public function statuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DocumentStatus::active()->get(),
        ]);
    }

    /**
     * Documents nécessitant une revue
     */
    public function needingReview(): JsonResponse
    {
        $documents = $this->documentService->getDocumentsNeedingReview();

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Supprimer un document (Admin)
     */
    public function destroy(Document $document): JsonResponse
    {
        if (!request()->user()->hasPermission('document.delete')) {
            return response()->json(['success' => false, 'message' => 'Permission refusée'], 403);
        }

        try {
            $this->documentService->deleteDocument($document, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Préparer les données pour l'impression
     */
    public function print(Document $document): JsonResponse
    {
        $document->load([
            'category',
            'type',
            'status',
            'owner',
            'currentVersionRelation',
            'versions',
        ]);

        // On récupère aussi les signatures pour l'affichage du cartouche de signature
        $signatures = \App\Models\GED\ElectronicSignature::forDocument($document->id)
            ->where('is_valid', true)
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'document' => $document,
                'signatures' => $signatures,
            ],
        ]);
    }

    /**
     * Dashboard statistiques
     */
    public function dashboard(): JsonResponse
    {
        $user = request()->user();

        // Documents en vigueur (EFFECTIVE)
        $effectiveCount = Document::active()->withStatus(DocumentStatus::EFFECTIVE)->count();
        
        // Calcul du changement (simplifié: comparaison avec le mois dernier sur la date de création ou de mise en vigueur)
        $lastMonthEffective = Document::active()
            ->withStatus(DocumentStatus::EFFECTIVE)
            ->where('effective_date', '<', now()->subMonth())
            ->count();
        
        $effectiveChange = $lastMonthEffective > 0 
            ? round((($effectiveCount - $lastMonthEffective) / $lastMonthEffective) * 100, 1)
            : ($effectiveCount > 0 ? 100 : 0);

        // Mes tâches (via WorkflowService)
        $myTasksCount = $this->workflowService->getPendingWorkflowsForUser($user)->count();

        // Activité récente (Audit logs mappés pour le frontend)
        $recentActivity = AuditLog::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($log) {
                // Mapping action to frontend-compatible keys
                $actionMap = [
                    'create' => 'document_created',
                    'update' => 'document_updated',
                    'approve' => 'workflow_approved',
                    'reject' => 'workflow_rejected',
                    'view' => 'document_viewed',
                    'login' => 'user_login',
                ];
                
                return [
                    'id' => $log->id,
                    'user' => [
                        'name' => $log->user_name,
                    ],
                    'action' => $actionMap[$log->action] ?? $log->action,
                    'created_at' => $log->created_at,
                ];
            });

        // Alertes de conformité
        $complianceAlerts = [];
        
        // Documents expirés
        $expiredCount = Document::active()->whereNotNull('expiry_date')->where('expiry_date', '<', now())->count();
        if ($expiredCount > 0) {
            $complianceAlerts[] = [
                'id' => 'expired_docs',
                'message' => "{$expiredCount} document(s) expiré(s) nécessitent une action.",
                'type' => 'danger'
            ];
        }

        // Revues en retard
        $overdueReview = Document::active()->whereNotNull('review_date')->where('review_date', '<', now())->count();
        if ($overdueReview > 0) {
            $complianceAlerts[] = [
                'id' => 'overdue_review',
                'message' => "{$overdueReview} document(s) ont une date de revue dépassée.",
                'type' => 'warning'
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'effective_documents' => $effectiveCount,
                    'effective_change' => $effectiveChange,
                    'pending_approval' => Document::active()->withStatus(DocumentStatus::PENDING_APPROVAL)->count(),
                    'my_pending_tasks' => $myTasksCount,
                    'review_due' => Document::active()->whereNotNull('review_date')->whereBetween('review_date', [now(), now()->addDays(30)])->count(),
                ],
                'recent_documents' => Document::active()
                    ->with(['category', 'status', 'owner'])
                    ->latest()
                    ->limit(5)
                    ->get(),
                'recent_activity' => $recentActivity,
                'compliance_alerts' => $complianceAlerts,
            ],
        ]);
    }
}
