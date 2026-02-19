<?php

namespace App\Http\Controllers\GED\Admin;

use App\Http\Controllers\Controller;
use App\Models\GED\Entity;
use App\Models\GED\Departement;
use App\Models\GED\Fonction;
use Illuminate\Http\JsonResponse;

use App\Services\GED\AuditService;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get all Entities
     */
    public function getEntities(): JsonResponse
    {
        return response()->json(Entity::all());
    }

    /**
     * Get single Entity
     */
    public function showEntity(Entity $entity): JsonResponse
    {
        return response()->json($entity);
    }

    /**
     * Update Entity
     */
    public function updateEntity(Request $request, Entity $entity): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Note: For now we only handle text fields as per standard premium UI pattern in this project.
        // If image handling is needed later, it can be added.
        
        $oldValues = $entity->toArray();
        $entity->update($validated);

        $this->auditService->log(
            'entity_updated',
            'system',
            "Entité modifiée: {$entity->name}",
            $entity,
            $oldValues,
            $entity->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Entité mise à jour avec succès',
            'data' => $entity
        ]);
    }

    /**
     * Delete Entity
     */
    public function destroyEntity(Request $request, Entity $entity): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if entity has departments before deleting
        if ($entity->departements()->count() > 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Impossible de supprimer cette entité car elle contient des départements.'
            ], 422);
        }

        $oldValues = $entity->toArray();
        $name = $entity->name;
        $entity->delete();

        $this->auditService->log(
            'entity_deleted',
            'system',
            "Entité supprimée: {$name}",
            null,
            $oldValues,
            []
        );

        return response()->json([
            'success' => true,
            'message' => 'Entité supprimée avec succès'
        ]);
    }

    /**
     * Get Departments by Entity
     */
    public function getDepartments(Entity $entity): JsonResponse
    {
        return response()->json($entity->departements);
    }

    /**
     * Get Functions by Department
     */
    public function getFunctions(Departement $departement): JsonResponse
    {
        return response()->json($departement->fonctions);
    }

    /**
     * Get all Departments
     */
    public function getAllDepartments(): JsonResponse
    {
        return response()->json(Departement::with('entity')->get());
    }

    /**
     * Get all Functions
     */
    public function getAllFunctions(): JsonResponse
    {
        return response()->json(Fonction::with('departement.entity')->get());
    }

    /**
     * Store Department
     */
    public function storeDepartment(Request $request): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entitie_id' => 'required|exists:entities,id',
        ]);

        $dept = Departement::create($validated);

        $this->auditService->log(
            'department_created',
            'system',
            "Département créé: {$dept->name}",
            $dept,
            [],
            $dept->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Département ajouté avec succès',
            'data' => $dept
        ]);
    }

    /**
     * Update Department
     */
    public function updateDepartment(Request $request, Departement $departement): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entitie_id' => 'required|exists:entities,id',
        ]);

        $oldValues = $departement->toArray();
        $departement->update($validated);

        $this->auditService->log(
            'department_updated',
            'system',
            "Département modifié: {$departement->name}",
            $departement,
            $oldValues,
            $departement->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Département mis à jour avec succès',
            'data' => $departement
        ]);
    }

    /**
     * Delete Department
     */
    public function destroyDepartment(Request $request, Departement $departement): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($departement->fonctions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce département car il contient des fonctions.'
            ], 422);
        }

        $oldValues = $departement->toArray();
        $name = $departement->name;
        $departement->delete();

        $this->auditService->log(
            'department_deleted',
            'system',
            "Département supprimé: {$name}",
            null,
            $oldValues,
            []
        );

        return response()->json([
            'success' => true,
            'message' => 'Département supprimé avec succès'
        ]);
    }

    /**
     * Store Function
     */
    public function storeFunction(Request $request): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'departement_id' => 'required|exists:departements,id',
        ]);

        $fn = Fonction::create($validated);

        $this->auditService->log(
            'function_created',
            'system',
            "Fonction créée: {$fn->name}",
            $fn,
            [],
            $fn->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fonction ajoutée avec succès',
            'data' => $fn
        ]);
    }

    /**
     * Update Function
     */
    public function updateFunction(Request $request, Fonction $fonction): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'departement_id' => 'required|exists:departements,id',
        ]);

        $oldValues = $fonction->toArray();
        $fonction->update($validated);

        $this->auditService->log(
            'function_updated',
            'system',
            "Fonction modifiée: {$fonction->name}",
            $fonction,
            $oldValues,
            $fonction->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fonction mise à jour avec succès',
            'data' => $fonction
        ]);
    }

    /**
     * Delete Function
     */
    public function destroyFunction(Request $request, Fonction $fonction): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if function is assigned to users if necessary, but typically we just delete it.
        // Assuming we want to be safe:
        if (\App\Models\User::where('fonction_id', $fonction->id)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette fonction car elle est assignée à des utilisateurs.'
            ], 422);
        }

        $oldValues = $fonction->toArray();
        $name = $fonction->name;
        $fonction->delete();

        $this->auditService->log(
            'function_deleted',
            'system',
            "Fonction supprimée: {$name}",
            null,
            $oldValues,
            []
        );

        return response()->json([
            'success' => true,
            'message' => 'Fonction supprimée avec succès'
        ]);
    }
}

