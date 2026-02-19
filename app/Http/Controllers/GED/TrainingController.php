<?php

namespace App\Http\Controllers\GED;

use App\Http\Controllers\Controller;
use App\Models\GED\TrainingRecord;
use App\Models\GED\Document;
use App\Services\GED\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    protected TrainingService $trainingService;

    public function __construct(TrainingService $trainingService)
    {
        $this->trainingService = $trainingService;
    }

    /**
     * Liste des formations de l'utilisateur connecté
     */
    public function index(): JsonResponse
    {
        $trainings = $this->trainingService->getPendingTrainings(Auth::user());

        return response()->json([
            'success' => true,
            'data' => $trainings,
        ]);
    }

    /**
     * Liste de toutes les formations (Admin)
     */
    public function all(Request $request): JsonResponse
    {
        if (!Auth::user()->hasPermission('training.view_all')) {
            return response()->json(['success' => false, 'message' => 'Permission refusée'], 403);
        }

        $trainings = $this->trainingService->getAllTrainings($request->all());

        return response()->json([
            'success' => true,
            'data' => $trainings,
        ]);
    }

    /**
     * Voir une formation spécifique
     */
    public function show(TrainingRecord $record): JsonResponse
    {
        $record->load(['user', 'document', 'documentVersion', 'assignedByUser', 'signature']);
        
        return response()->json([
            'success' => true,
            'data' => $record,
        ]);
    }

    /**
     * Assigner une formation
     */
    public function assign(Request $request): JsonResponse
    {
        if (!Auth::user()->hasPermission('training.assign')) {
            return response()->json(['success' => false, 'message' => 'Permission refusée'], 403);
        }

        $request->validate([
            'document_id' => 'required|exists:ged_documents,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'due_date' => 'nullable|date|after:today',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $document = Document::findOrFail($request->document_id);
            $this->trainingService->assignTraining(
                $document,
                $request->user_ids,
                Auth::user(),
                $request->reason,
                $request->due_date
            );

            return response()->json([
                'success' => true,
                'message' => 'Formation assignée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Accuser réception
     */
    public function acknowledge(Request $request, TrainingRecord $record): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            $this->trainingService->acknowledgeTraining($record, Auth::user(), $request->pin, $request->comment);

            return response()->json([
                'success' => true,
                'message' => 'Formation validée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Démarrer une formation
     */
    public function start(TrainingRecord $record): JsonResponse
    {
        try {
            $this->trainingService->startTraining($record, Auth::user());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
