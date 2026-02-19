<?php

namespace App\Http\Controllers\GED;

use App\Http\Controllers\Controller;
use App\Models\GED\Document;
use App\Models\GED\Workflow;
use App\Models\GED\WorkflowInstance;
use App\Services\GED\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * GED Workflow Controller
 * 
 * API pour les workflows d'approbation multi-niveaux
 */
class WorkflowController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Liste des workflows disponibles
     */
    public function workflows(): JsonResponse
    {
        $workflows = Workflow::active()
            ->with('steps')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workflows,
        ]);
    }

    /**
     * Liste des instances de workflows (tous)
     */
    public function instances(Request $request): JsonResponse
    {
        $instances = WorkflowInstance::with([
            'workflow',
            'document',
            'initiator',
            'currentStep',
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $instances,
        ]);
    }

    /**
     * Mes workflows en attente d'action
     */
    public function myPending(Request $request): JsonResponse
    {
        $instances = $this->workflowService->getPendingWorkflowsForUser($request->user());

        return response()->json([
            'success' => true,
            'data' => $instances,
        ]);
    }

    /**
     * Initier un workflow sur un document
     */
    public function initiate(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'workflow_id' => 'required|integer|exists:ged_workflows,id',
        ]);

        try {
            $workflow = Workflow::findOrFail($validated['workflow_id']);
            $instance = $this->workflowService->initiateWorkflow($document, $workflow, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Workflow initié',
                'data' => $instance->load(['workflow', 'currentStep']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Soumettre un workflow pour approbation
     */
    public function submit(Request $request, WorkflowInstance $instance): JsonResponse
    {
        try {
            $instance = $this->workflowService->submitWorkflow($instance, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Workflow soumis pour approbation',
                'data' => $instance->load(['workflow', 'currentStep']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Afficher un workflow instance
     */
    public function show(WorkflowInstance $instance): JsonResponse
    {
        $instance->load([
            'workflow.steps',
            'document',
            'documentVersion',
            'currentStep',
            'initiator',
            'actions.user',
            'actions.workflowStep',
            'actions.signature',
        ]);

        return response()->json([
            'success' => true,
            'data' => $instance,
        ]);
    }

    /**
     * Approuver l'étape courante
     */
    public function approve(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'nullable|string|max:2000',
            'signature_pin' => 'nullable|string',
        ]);

        try {
            $instance = $this->workflowService->approveStep(
                $instance,
                $request->user(),
                $validated['comment'] ?? null,
                $validated['signature_pin'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Étape approuvée',
                'data' => $instance->load(['workflow', 'currentStep', 'actions']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rejeter le workflow
     */
    public function reject(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
            'signature_pin' => 'nullable|string',
        ]);

        try {
            $instance = $this->workflowService->rejectWorkflow(
                $instance,
                $request->user(),
                $validated['reason'],
                $validated['signature_pin'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Workflow rejeté',
                'data' => $instance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Demander une révision
     */
    public function requestRevision(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        try {
            $instance = $this->workflowService->requestRevision(
                $instance,
                $request->user(),
                $validated['comment']
            );

            return response()->json([
                'success' => true,
                'message' => 'Révision demandée',
                'data' => $instance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Annuler un workflow
     */
    public function cancel(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $instance = $this->workflowService->cancelWorkflow(
                $instance,
                $request->user(),
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Workflow annulé',
                'data' => $instance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Historique des workflows d'un document
     */
    public function documentHistory(Document $document): JsonResponse
    {
        $instances = WorkflowInstance::where('document_id', $document->id)
            ->with(['workflow', 'initiator', 'actions.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $instances,
        ]);
    }
}
