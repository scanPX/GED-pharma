<?php

namespace App\Http\Controllers\GED;

use App\Http\Controllers\Controller;
use App\Models\GED\AuditLog;
use App\Models\GED\Document;
use App\Services\GED\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * GED Audit Trail Controller
 * 
 * API pour la traçabilité et l'audit trail GMP
 */
class AuditController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Recherche dans l'audit trail
     */
    public function index(Request $request): JsonResponse
    {
        // Vérifier les permissions
        if (!$request->user()->canViewAuditTrail()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à l\'audit trail',
            ], 403);
        }

        $validated = $request->validate([
            'document_id' => 'nullable|integer|exists:ged_documents,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'category' => 'nullable|string|in:document,workflow,user,system,access,signature,training',
            'action' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'gmp_critical_only' => 'nullable|boolean',
            'security_events_only' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:10|max:500',
            'search' => 'nullable|string|max:255',
        ]);

        $query = AuditLog::with(['user', 'document'])
            ->orderBy('occurred_at', 'desc');

        if (!empty($validated['search'])) {
            $query->search($validated['search']);
        }

        if (!empty($validated['document_id'])) {
            $query->forDocument($validated['document_id']);
        }

        if (!empty($validated['user_id'])) {
            $query->forUser($validated['user_id']);
        }

        if (!empty($validated['category'])) {
            $query->forCategory($validated['category']);
        }

        if (!empty($validated['action'])) {
            $query->forAction($validated['action']);
        }

        if (!empty($validated['from_date']) && !empty($validated['to_date'])) {
            $query->inDateRange($validated['from_date'], $validated['to_date']);
        }

        if (!empty($validated['gmp_critical_only'])) {
            $query->gmpCritical();
        }

        if (!empty($validated['security_events_only'])) {
            $query->securityEvents();
        }

        $logs = $query->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Audit trail d'un document spécifique
     */
    public function documentAuditTrail(Document $document): JsonResponse
    {
        if (!request()->user()->canViewAuditTrail()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $logs = AuditLog::forDocument($document->id)
            ->with('user')
            ->orderBy('occurred_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'document' => [
                    'id' => $document->id,
                    'document_number' => $document->document_number,
                    'title' => $document->title,
                ],
                'audit_trail' => $logs,
            ],
        ]);
    }

    /**
     * Vérifier l'intégrité de l'audit trail
     */
    public function verifyIntegrity(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('audit.verify')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $errors = $this->auditService->verifyIntegrity();

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => count($errors) === 0,
                'total_entries' => AuditLog::count(),
                'errors_found' => count($errors),
                'errors' => $errors,
                'verified_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Générer un rapport d'audit
     */
    public function generateReport(Request $request): JsonResponse
    {
        if (!$request->user()->canViewAuditTrail()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'document_id' => 'nullable|integer|exists:ged_documents,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'category' => 'nullable|string',
            'action' => 'nullable|string',
            'gmp_critical_only' => 'nullable|boolean',
        ]);

        $report = $this->auditService->generateAuditReport(
            new \DateTime($validated['from_date']),
            new \DateTime($validated['to_date']),
            array_filter([
                'document_id' => $validated['document_id'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'category' => $validated['category'] ?? null,
                'action' => $validated['action'] ?? null,
                'gmp_critical_only' => $validated['gmp_critical_only'] ?? false,
            ])
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Exporter l'audit trail (format CSV)
     */
    public function export(Request $request)
    {
        if (!$request->user()->canViewAuditTrail()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'document_id' => 'nullable|integer',
        ]);

        $query = AuditLog::inDateRange($validated['from_date'], $validated['to_date'])
            ->with(['user', 'document'])
            ->orderBy('occurred_at');

        if (!empty($validated['document_id'])) {
            $query->forDocument($validated['document_id']);
        }

        $logs = $query->get();

        // Générer le CSV
        $filename = 'audit_trail_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, [
                'Date/Heure',
                'Utilisateur',
                'Action',
                'Catégorie',
                'Description',
                'Document',
                'Version',
                'Adresse IP',
                'Statut',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->occurred_at->format('Y-m-d H:i:s'),
                    $log->user_name ?? 'Système',
                    $log->action,
                    $log->action_category,
                    $log->action_description,
                    $log->document_number ?? '-',
                    $log->document_version ?? '-',
                    $log->ip_address ?? '-',
                    $log->status,
                ]);
            }

            fclose($file);
        };

        // Log de l'export
        $this->auditService->log(
            'export',
            'audit',
            'Export de l\'audit trail',
            null,
            [],
            ['period' => "{$validated['start_date']} - {$validated['end_date']}"]
        );

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Statistiques de l'audit trail
     */
    public function statistics(Request $request): JsonResponse
    {
        if (!$request->user()->canViewAuditTrail()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        return response()->json([
            'success' => true,
            'data' => [
                'total_entries' => AuditLog::count(),
                'entries_last_period' => AuditLog::where('occurred_at', '>=', $startDate)->count(),
                'by_category' => AuditLog::where('occurred_at', '>=', $startDate)
                    ->selectRaw('action_category, COUNT(*) as count')
                    ->groupBy('action_category')
                    ->pluck('count', 'action_category'),
                'by_action' => AuditLog::where('occurred_at', '>=', $startDate)
                    ->selectRaw('action, COUNT(*) as count')
                    ->groupBy('action')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->pluck('count', 'action'),
                'gmp_critical_count' => AuditLog::where('occurred_at', '>=', $startDate)
                    ->gmpCritical()
                    ->count(),
                'security_events_count' => AuditLog::where('occurred_at', '>=', $startDate)
                    ->securityEvents()
                    ->count(),
                'top_users' => AuditLog::where('occurred_at', '>=', $startDate)
                    ->whereNotNull('user_id')
                    ->selectRaw('user_name, COUNT(*) as count')
                    ->groupBy('user_name')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get(),
            ],
        ]);
    }
}
