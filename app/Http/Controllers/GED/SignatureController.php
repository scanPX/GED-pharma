<?php

namespace App\Http\Controllers\GED;

use App\Http\Controllers\Controller;
use App\Models\GED\ElectronicSignature;
use App\Services\GED\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignatureController extends Controller
{
    protected SignatureService $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Liste des signatures (Admin)
     */
    public function index(Request $request): JsonResponse
    {
        if (!Auth::user()->hasPermission('signature.view_all')) {
            return response()->json(['success' => false, 'message' => 'Permission refusée'], 403);
        }

        $query = ElectronicSignature::with(['user', 'document', 'documentVersion']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('document_id')) {
            $query->where('document_id', $request->document_id);
        }

        if ($request->has('is_revoked')) {
            $query->where('is_revoked', $request->boolean('is_revoked'));
        }

        $signatures = $query->orderBy('signed_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $signatures,
        ]);
    }

    /**
     * Voir une signature
     */
    public function show(ElectronicSignature $signature): JsonResponse
    {
        $signature->load(['user', 'document', 'documentVersion', 'revokedByUser']);

        return response()->json([
            'success' => true,
            'data' => $signature,
        ]);
    }

    /**
     * Vérifier une signature
     */
    public function verify(ElectronicSignature $signature): JsonResponse
    {
        $verification = $this->signatureService->verifySignature($signature);

        return response()->json([
            'success' => true,
            'data' => $verification,
        ]);
    }

    /**
     * Révoquer une signature
     */
    public function revoke(Request $request, ElectronicSignature $signature): JsonResponse
    {
        if (!Auth::user()->hasPermission('signature.revoke')) {
            return response()->json(['success' => false, 'message' => 'Permission refusée'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->signatureService->revokeSignature($signature, Auth::user(), $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Signature révoquée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
