<?php

namespace App\Http\Controllers\GED\Admin;

use App\Http\Controllers\Controller;
use App\Models\GED\Workflow;
use App\Models\GED\WorkflowStep;
use App\Models\GED\DocumentType;
use App\Services\GED\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin Workflow Management Controller
 * 
 * CRUD for workflow definitions and steps configuration.
 */
class WorkflowMgmtController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * List all workflow definitions
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $workflows = Workflow::withCount('steps')
            ->with('documentTypes')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workflows
        ]);
    }

    /**
     * Store a new workflow definition
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:ged_workflows,code',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['approval', 'review', 'validation', 'change_control'])],
            'requires_sequential_approval' => 'boolean',
            'allows_parallel_approval' => 'boolean',
            'requires_all_approvers' => 'boolean',
            'min_approvers' => 'integer|min:1',
            'allows_delegation' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $workflow = Workflow::create($validated);

        $this->auditService->log(
            'workflow_definition_created',
            'system',
            "Workflow definition created: {$workflow->name} ({$workflow->code})",
            $workflow,
            [],
            $workflow->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Workflow créé avec succès',
            'data' => $workflow
        ], 201);
    }

    /**
     * Show workflow detail with steps
     */
    public function show(Request $request, Workflow $workflow): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $workflow->load(['steps' => function($q) {
            $q->orderBy('step_order');
        }, 'documentTypes']);

        return response()->json([
            'success' => true,
            'data' => $workflow
        ]);
    }

    /**
     * Update workflow definition
     */
    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['approval', 'review', 'validation', 'change_control'])],
            'requires_sequential_approval' => 'boolean',
            'allows_parallel_approval' => 'boolean',
            'requires_all_approvers' => 'boolean',
            'min_approvers' => 'integer|min:1',
            'allows_delegation' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $oldValues = $workflow->toArray();
        $workflow->update($validated);

        $this->auditService->log(
            'workflow_definition_updated',
            'system',
            "Workflow definition updated: {$workflow->name}",
            $workflow,
            $oldValues,
            $workflow->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Workflow mis à jour',
            'data' => $workflow
        ]);
    }

    /**
     * Assign workflow to document types
     */
    public function assignToDocumentTypes(Request $request, Workflow $workflow): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'document_type_ids' => 'required|array',
            'document_type_ids.*' => 'exists:ged_document_types,id',
            'default_type_id' => 'nullable|exists:ged_document_types,id',
        ]);

        DB::transaction(function() use ($workflow, $validated) {
            $syncData = [];
            foreach ($validated['document_type_ids'] as $id) {
                $syncData[$id] = [
                    'is_default' => $id == ($validated['default_type_id'] ?? null)
                ];
            }
            $workflow->documentTypes()->sync($syncData);

            $this->auditService->log(
                'workflow_assigned_to_types',
                'system',
                "Workflow '{$workflow->name}' assigned to document types",
                $workflow,
                [],
                ['document_type_ids' => $validated['document_type_ids']]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Affectations mises à jour',
            'data' => $workflow->load('documentTypes')
        ]);
    }

    /**
     * Add a step to the workflow
     */
    public function addStep(Request $request, Workflow $workflow): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'step_type' => ['required', Rule::in(['review', 'approval', 'signature', 'qa_approval', 'regulatory_approval', 'final_approval'])],
            'required_role_id' => 'nullable|exists:ged_roles,id',
            'required_user_id' => 'nullable|exists:users,id',
            'allowed_roles' => 'nullable|array',
            'any_user_with_permission' => 'boolean',
            'requires_comment' => 'boolean',
            'requires_signature' => 'boolean',
            'timeout_days' => 'nullable|integer|min:1',
            'target_status_id' => 'nullable|exists:ged_document_statuses,id',
            'rejection_status_id' => 'nullable|exists:ged_document_statuses,id',
        ]);

        // Auto-assign order
        $maxOrder = $workflow->steps()->max('step_order') ?? 0;
        $validated['step_order'] = $maxOrder + 1;
        $validated['workflow_id'] = $workflow->id;

        $step = WorkflowStep::create($validated);

        $this->auditService->log(
            'workflow_step_created',
            'system',
            "Step '{$step->name}' added to workflow '{$workflow->name}'",
            $step
        );

        return response()->json([
            'success' => true,
            'message' => 'Étape ajoutée',
            'data' => $step
        ], 201);
    }

    /**
     * Update a workflow step
     */
    public function updateStep(Request $request, Workflow $workflow, WorkflowStep $step): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($step->workflow_id !== $workflow->id) {
            return response()->json(['success' => false, 'message' => 'Step does not belong to this workflow'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'step_type' => ['required', Rule::in(['review', 'approval', 'signature', 'qa_approval', 'regulatory_approval', 'final_approval'])],
            'required_role_id' => 'nullable|exists:ged_roles,id',
            'required_user_id' => 'nullable|exists:users,id',
            'allowed_roles' => 'nullable|array',
            'any_user_with_permission' => 'boolean',
            'requires_comment' => 'boolean',
            'requires_signature' => 'boolean',
            'timeout_days' => 'nullable|integer|min:1',
            'target_status_id' => 'nullable|exists:ged_document_statuses,id',
            'rejection_status_id' => 'nullable|exists:ged_document_statuses,id',
            'is_active' => 'boolean',
        ]);

        $oldValues = $step->toArray();
        $step->update($validated);

        $this->auditService->log(
            'workflow_step_updated',
            'system',
            "Step '{$step->name}' updated in workflow '{$workflow->name}'",
            $step,
            $oldValues,
            $step->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Étape mise à jour',
            'data' => $step
        ]);
    }

    /**
     * Reorder workflow steps
     */
    public function reorderSteps(Request $request, Workflow $workflow): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'steps' => 'required|array',
            'steps.*.id' => 'required|exists:ged_workflow_steps,id',
            'steps.*.order' => 'required|integer',
        ]);

        DB::transaction(function() use ($workflow, $validated) {
            foreach ($validated['steps'] as $item) {
                WorkflowStep::where('id', $item['id'])
                    ->where('workflow_id', $workflow->id)
                    ->update(['step_order' => $item['order']]);
            }

            $this->auditService->log(
                'workflow_steps_reordered',
                'system',
                "Steps reordered for workflow '{$workflow->name}'",
                $workflow,
                [],
                ['steps' => $validated['steps']]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Ordre des étapes mis à jour'
        ]);
    }

    /**
     * Delete a workflow step
     */
    public function removeStep(Request $request, Workflow $workflow, WorkflowStep $step): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($step->workflow_id !== $workflow->id) {
            return response()->json(['success' => false, 'message' => 'Step does not belong to this workflow'], 400);
        }

        $stepName = $step->name;
        $step->delete();

        // Reorder remaining steps
        $steps = $workflow->steps()->orderBy('step_order')->get();
        foreach ($steps as $index => $s) {
            $s->update(['step_order' => $index + 1]);
        }

        $this->auditService->log(
            'workflow_step_deleted',
            'system',
            "Step '{$stepName}' removed from workflow '{$workflow->name}'"
        );

        return response()->json([
            'success' => true,
            'message' => 'Étape supprimée'
        ]);
    }

    /**
     * Delete a workflow definition
     */
    public function destroy(Request $request, Workflow $workflow): JsonResponse
    {
        if (!$request->user()->hasPermission('workflow.manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check for active instances
        if ($workflow->instances()->active()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un workflow ayant des instances actives.'
            ], 400);
        }

        $workflowName = $workflow->name;
        $workflow->delete();

        $this->auditService->log(
            'workflow_definition_deleted',
            'system',
            "Workflow definition deleted: {$workflowName}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Workflow supprimé'
        ]);
    }
}
