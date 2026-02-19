<?php

namespace App\Http\Controllers\GED\Admin;

use App\Http\Controllers\Controller;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use App\Services\GED\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of roles and permissions.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->can('user.manage')) { // Re-using user.manage for now, or create role.manage?
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('module');

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    /**
     * Update the specified role's permissions.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:ged_permissions,name'] // Sending permission names
        ]);

        DB::beginTransaction();
        try {
            $oldPermissions = $role->permissions->pluck('name')->toArray();
            $newPermissions = $validated['permissions'] ?? [];

            // Sync permissions
            // We need to resolve IDs from names
            $permissionIds = Permission::whereIn('name', $newPermissions)->pluck('id');
            
            $role->permissions()->syncWithPivotValues($permissionIds, [
                'granted_by' => $request->user()->id,
                'granted_at' => now(),
            ]);

            // Audit
            $this->auditService->log(
                'role_updated',
                'access',
                "Role updated: {$role->name}",
                $role,
                ['permissions' => $oldPermissions],
                ['permissions' => $newPermissions]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role permissions updated successfully.',
                'role' => $role->refresh()->load('permissions')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }
}
