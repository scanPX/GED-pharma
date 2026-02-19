<?php

namespace App\Http\Controllers\GED\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GED\Admin\StoreUserRequest;
use App\Http\Requests\GED\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\GED\Role;
use App\Services\GED\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * Admin User Management Controller
 * 
 * Centralized control for user lifecycle management.
 * critical for 21 CFR Part 11 "Management Controls".
 */
class UserController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * List users with filtering
     */
    /**
     * List users with filtering
     */
    public function index(Request $request): JsonResponse
    {
        // Permission check
        if (!$request->user()->can('user.manage')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::with(['roles', 'department']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Create a new user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Structure handling
            // We expect department_id and fonction_id from the frontend now.
            // If the old 'department' string is sent, we ignore it or could map it if needed, 
            // but the requirement is to use the new select fields.
            
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'department_id' => $request->department_id,
                'fonction_id' => $request->fonction_id,
                'title' => $request->title,
                'must_change_password' => $request->must_change_password ?? true,
                'is_active' => true,
            ];

            $user = User::create($userData);

            // Assign Role
            $role = Role::where('name', $request->role)->firstOrFail();
            $user->assignRole($role);

            // Audit Log (Critical Event)
            $this->auditService->log(
                'user_created',
                'access',
                "User created: {$user->email} with role {$role->name}",
                $user,
                [],
                $user->toArray(),
                [],
                null,
                'user',
                $user->id,
                ['role' => $role->name]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load(['roles', 'department', 'fonction'])
            ], 201);

        } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create user: ' . $e->getMessage()
        ], 500);
    }
    }

    /**
     * Update user details
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::beginTransaction();
        try {
            $oldValues = $user->toArray();
            
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'department_id' => $request->department_id,
                'fonction_id' => $request->fonction_id,
                'title' => $request->title,
                'is_active' => $request->boolean('is_active'), // ensure boolean
            ];

            // Password update logic
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
                $updateData['must_change_password'] = $request->boolean('must_change_password');
            }

            $user->update($updateData);

            // Role Update
            if ($request->has('role')) {
                $newRole = Role::where('name', $request->role)->firstOrFail();
                if (!$user->hasRole($newRole->name)) {
                    $user->roles()->sync([$newRole->id]);
                    // Log Role Change specifically
                    $this->auditService->log(
                        'user_role_updated',
                        'access',
                        "User role changed to {$newRole->name}",
                        $user,
                        ['role' => $user->roles->first()?->name],
                        ['role' => $newRole->name]
                    );
                }
            }

            // General Update Audit
            $this->auditService->log(
                'user_updated',
                'access',
                "User details updated for {$user->email}",
                $user,
                $oldValues,
                $user->toArray()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->load(['roles', 'department', 'fonction'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle Active Status (Disable/Enable)
     */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->can('user.manage')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate(['is_active' => 'required|boolean']);
        
        $oldStatus = $user->is_active;
        $user->is_active = $validated['is_active'];
        $user->save();

        // Specific Audit for Access Control
        $action = $user->is_active ? 'user_enabled' : 'user_disabled';
        $desc = $user->is_active ? "User access restored: {$user->email}" : "User access revoked: {$user->email}";

        $this->auditService->log(
            $action,
            'access', // Category
            $desc,
            $user,
            ['is_active' => $oldStatus],
            ['is_active' => $user->is_active],
            ['severity' => 'critical'] // Mark as security critical
        );

        // If disabling, we should also revoke tokens ideally
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'User status updated',
            'data' => $user
        ]);
    }
}
