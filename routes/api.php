<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GED\AuthController;
use App\Http\Controllers\GED\DocumentController;
use App\Http\Controllers\GED\WorkflowController;
use App\Http\Controllers\GED\AuditController;
use App\Http\Controllers\GED\TrainingController;
use App\Http\Controllers\GED\SignatureController;

/**
 * GED - Gestion Électronique de Documents
 * Routes API pour le module DMS pharmaceutique
 * 
 * Préfixe: /api/ged
 */

// ========== AUTHENTIFICATION (publique) ==========
Route::prefix('ged/auth')->name('ged.auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Fallback for Laravel's default auth redirect
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthorized. Please login.'], 401);
})->name('login');

// ========== ROUTES AUTHENTIFIÉES ==========
Route::middleware(['auth:sanctum'])->prefix('ged')->name('ged.')->group(function () {
    
    // ========== AUTHENTIFICATION ==========
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
        
        // Vérifications pour signatures électroniques (21 CFR Part 11)
        Route::post('/verify-password', [AuthController::class, 'verifyPassword'])->name('verify-password');
        Route::post('/verify-pin', [AuthController::class, 'verifyPin'])->name('verify-pin');
        
        // Gestion du compte
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
        Route::post('/set-signature-pin', [AuthController::class, 'setSignaturePin'])->name('set-signature-pin');
    });

    // ========== DASHBOARD ==========
    Route::get('/dashboard', [DocumentController::class, 'dashboard'])->name('dashboard');

    // ========== ADMIN MODULES ==========
    Route::prefix('admin')->name('admin.')->group(function () {
        // User Management
        Route::apiResource('users', \App\Http\Controllers\GED\Admin\UserController::class);
        Route::patch('users/{user}/toggle-active', [\App\Http\Controllers\GED\Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');
        
        Route::get('roles', [\App\Http\Controllers\GED\Admin\RoleController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [\App\Http\Controllers\GED\Admin\RoleController::class, 'update'])->name('roles.update');

        // System Settings
        Route::get('settings', [\App\Http\Controllers\GED\Admin\ConfigController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\GED\Admin\ConfigController::class, 'update'])->name('settings.update');

        // Structure Reference Data
        Route::get('entities', [\App\Http\Controllers\GED\Admin\StructureController::class, 'getEntities']);
        Route::get('entities/{entity}', [\App\Http\Controllers\GED\Admin\StructureController::class, 'showEntity']);
        Route::put('entities/{entity}', [\App\Http\Controllers\GED\Admin\StructureController::class, 'updateEntity']);
        Route::delete('entities/{entity}', [\App\Http\Controllers\GED\Admin\StructureController::class, 'destroyEntity']);
        
        Route::get('entities/{entity}/departments', [\App\Http\Controllers\GED\Admin\StructureController::class, 'getDepartments']);
        Route::get('departments', [\App\Http\Controllers\GED\Admin\StructureController::class, 'getAllDepartments']);
        Route::post('departments', [\App\Http\Controllers\GED\Admin\StructureController::class, 'storeDepartment']);
        Route::put('departments/{departement}', [\App\Http\Controllers\GED\Admin\StructureController::class, 'updateDepartment']);
        Route::delete('departments/{departement}', [\App\Http\Controllers\GED\Admin\StructureController::class, 'destroyDepartment']);

        Route::get('departments/{departement}/functions', [\App\Http\Controllers\GED\Admin\StructureController::class, 'getFunctions']);
        Route::get('functions', [\App\Http\Controllers\GED\Admin\StructureController::class, 'getAllFunctions']);
        Route::post('functions', [\App\Http\Controllers\GED\Admin\StructureController::class, 'storeFunction']);
        Route::put('functions/{fonction}', [\App\Http\Controllers\GED\Admin\StructureController::class, 'updateFunction']);
        Route::delete('functions/{fonction}', [\App\Http\Controllers\GED\Admin\StructureController::class, 'destroyFunction']);

        // Workflow Management
        Route::apiResource('workflows', \App\Http\Controllers\GED\Admin\WorkflowMgmtController::class);
        Route::post('workflows/{workflow}/steps', [\App\Http\Controllers\GED\Admin\WorkflowMgmtController::class, 'addStep'])->name('workflows.steps.add');
        Route::put('workflows/{workflow}/steps/{step}', [\App\Http\Controllers\GED\Admin\WorkflowMgmtController::class, 'updateStep'])->name('workflows.steps.update');
        Route::delete('workflows/{workflow}/steps/{step}', [\App\Http\Controllers\GED\Admin\WorkflowMgmtController::class, 'removeStep'])->name('workflows.steps.remove');
        Route::post('workflows/{workflow}/reorder-steps', [\App\Http\Controllers\GED\Admin\WorkflowMgmtController::class, 'reorderSteps'])->name('workflows.steps.reorder');
        Route::post('workflows/{workflow}/assign-document-types', [\App\Http\Controllers\GED\Admin\WorkflowMgmtController::class, 'assignToDocumentTypes'])->name('workflows.assign-types');
    });

    // ========== DOCUMENTS ==========
    Route::prefix('documents')->name('documents.')->group(function () {
        // Liste et recherche
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        
        // Références (catégories, types, statuts)
        Route::get('/categories', [DocumentController::class, 'categories'])->name('categories');
        Route::get('/types', [DocumentController::class, 'types'])->name('types');
        Route::get('/statuses', [DocumentController::class, 'statuses'])->name('statuses');
        
        // Documents nécessitant revue
        Route::get('/needing-review', [DocumentController::class, 'needingReview'])->name('needing-review');
        
        // CRUD
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::post('/{document}/archive', [DocumentController::class, 'archive'])->name('archive');
        Route::get('/{document}/print', [DocumentController::class, 'print'])->name('print');
        Route::get('/{document}/view/{versionId?}', [DocumentController::class, 'view'])->name('view');
        
        // Versioning
        Route::post('/{document}/versions', [DocumentController::class, 'createVersion'])->name('versions.store');
        Route::get('/{document}/download/{versionId?}', [DocumentController::class, 'download'])->name('download');
        
        // Audit trail du document
        Route::get('/{document}/audit-trail', [AuditController::class, 'documentAuditTrail'])->name('audit-trail');
        
        // Workflows du document
        Route::get('/{document}/workflows', [WorkflowController::class, 'documentHistory'])->name('workflows');
    });

    // ========== WORKFLOWS ==========
    Route::prefix('workflows')->name('workflows.')->group(function () {
        // Liste de toutes les instances de workflow
        Route::get('/', [WorkflowController::class, 'instances'])->name('index');
        // Liste des workflows configurés
        Route::get('/definitions', [WorkflowController::class, 'workflows'])->name('definitions');
        
        // Mes approbations en attente
        Route::get('/my-pending', [WorkflowController::class, 'myPending'])->name('my-pending');
        
        // Initier un workflow
        Route::post('/documents/{document}/initiate', [WorkflowController::class, 'initiate'])->name('initiate');
        
        // Actions sur workflow instance
        Route::get('/{instance}', [WorkflowController::class, 'show'])->name('show');
        Route::post('/{instance}/submit', [WorkflowController::class, 'submit'])->name('submit');
        Route::post('/{instance}/approve', [WorkflowController::class, 'approve'])->name('approve');
        Route::post('/{instance}/reject', [WorkflowController::class, 'reject'])->name('reject');
        Route::post('/{instance}/revision', [WorkflowController::class, 'requestRevision'])->name('revision');
        Route::post('/{instance}/cancel', [WorkflowController::class, 'cancel'])->name('cancel');
    });

    // ========== AUDIT TRAIL ==========
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditController::class, 'index'])->name('index');
        Route::get('/verify-integrity', [AuditController::class, 'verifyIntegrity'])->name('verify');
        Route::post('/report', [AuditController::class, 'generateReport'])->name('report');
        Route::get('/export', [AuditController::class, 'export'])->name('export');
        Route::get('/statistics', [AuditController::class, 'statistics'])->name('statistics');
    });

    // ========== TRAINING ==========
    Route::prefix('training')->name('training.')->group(function () {
        Route::get('/', [TrainingController::class, 'index'])->name('index');
        Route::get('/all', [TrainingController::class, 'all'])->name('all');
        Route::get('/{record}', [TrainingController::class, 'show'])->name('show');
        Route::post('/assign', [TrainingController::class, 'assign'])->name('assign');
        Route::post('/{record}/start', [TrainingController::class, 'start'])->name('start');
        Route::post('/{record}/acknowledge', [TrainingController::class, 'acknowledge'])->name('acknowledge');
    });

    // ========== SIGNATURES ==========
    Route::prefix('signatures')->name('signatures.')->group(function () {
        Route::get('/', [SignatureController::class, 'index'])->name('index');
        Route::get('/{signature}', [SignatureController::class, 'show'])->name('show');
        Route::get('/{signature}/verify', [SignatureController::class, 'verify'])->name('verify');
        Route::post('/{signature}/revoke', [SignatureController::class, 'revoke'])->name('revoke');
    });
});
