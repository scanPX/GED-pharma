<?php

namespace App\Http\Controllers\GED;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GED\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * AuthController - Authentification API pour GED Pharma
 * 
 * Gère l'authentification des utilisateurs conformément aux exigences:
 * - 21 CFR Part 11: Signatures électroniques
 * - GMP Annexe 11: Audit trail complet
 * - Validation des credentials
 */
class AuthController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Authentification de l'utilisateur
     * Conforme 21 CFR Part 11 - Exige authentification unique
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            $this->auditService->log(
                action: 'login_failed',
                modelType: 'User',
                modelId: null,
                details: [
                    'email' => $request->email,
                    'reason' => 'user_not_found',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                severity: 'warning'
            );

            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        // Vérification du compte actif
        if (!$user->is_active) {
            $this->auditService->log(
                action: 'login_failed',
                modelType: 'User',
                modelId: $user->id,
                details: [
                    'email' => $request->email,
                    'reason' => 'account_inactive',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                severity: 'warning'
            );

            throw ValidationException::withMessages([
                'email' => ['Ce compte a été désactivé. Contactez l\'administrateur.'],
            ]);
        }

        // Vérification du mot de passe
        if (!Hash::check($request->password, $user->password)) {
            // Incrémenter le compteur de tentatives échouées
            $user->increment('failed_login_attempts');
            
            // Verrouillage après 5 tentatives (conformité sécurité)
            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'is_active' => false,
                    'locked_at' => now(),
                ]);

                $this->auditService->log(
                    action: 'account_locked',
                    modelType: 'User',
                    modelId: $user->id,
                    details: [
                        'reason' => 'exceeded_login_attempts',
                        'attempts' => $user->failed_login_attempts,
                        'ip_address' => $request->ip(),
                    ],
                    severity: 'critical'
                );
            }

            $this->auditService->log(
                action: 'login_failed',
                modelType: 'User',
                modelId: $user->id,
                details: [
                    'email' => $request->email,
                    'reason' => 'invalid_password',
                    'attempt_number' => $user->failed_login_attempts,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                severity: 'warning'
            );

            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        // Vérification expiration du mot de passe (90 jours par défaut)
        $passwordMaxAge = config('ged.password_max_age_days', 90);
        if ($user->password_changed_at && $user->password_changed_at->addDays($passwordMaxAge)->isPast()) {
            $this->auditService->log(
                action: 'login_password_expired',
                modelType: 'User',
                modelId: $user->id,
                details: [
                    'password_changed_at' => $user->password_changed_at->toIso8601String(),
                    'max_age_days' => $passwordMaxAge,
                ],
                severity: 'warning'
            );

            return response()->json([
                'message' => 'Votre mot de passe a expiré.',
                'password_expired' => true,
            ], 403);
        }

        // Réinitialiser le compteur de tentatives échouées
        $user->update([
            'failed_login_attempts' => 0,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Créer le token Sanctum
        $deviceName = $request->device_name ?? 'GED-Web-' . substr(md5($request->userAgent()), 0, 8);
        $token = $user->createToken($deviceName, ['*'], now()->addHours(8));

        // Log de connexion réussie
        $this->auditService->log(
            action: 'login_success',
            modelType: 'User',
            modelId: $user->id,
            details: [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_name' => $deviceName,
            ],
            severity: 'info'
        );

        // Charger les relations nécessaires
        $user->load(['roles.permissions', 'department']);

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    /**
     * Déconnexion de l'utilisateur
     * Révocation du token actuel
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Révoquer le token actuel
        $request->user()->currentAccessToken()->delete();

        $this->auditService->log(
            action: 'logout',
            modelType: 'User',
            modelId: $user->id,
            details: [
                'ip_address' => $request->ip(),
            ],
            severity: 'info'
        );

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Déconnexion de tous les appareils
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $tokenCount = $user->tokens()->count();
        
        // Révoquer tous les tokens
        $user->tokens()->delete();

        $this->auditService->log(
            action: 'logout_all_devices',
            modelType: 'User',
            modelId: $user->id,
            details: [
                'tokens_revoked' => $tokenCount,
                'ip_address' => $request->ip(),
            ],
            severity: 'info'
        );

        return response()->json([
            'message' => 'Déconnecté de tous les appareils.',
            'tokens_revoked' => $tokenCount,
        ]);
    }

    /**
     * Récupérer le profil de l'utilisateur connecté
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['roles.permissions', 'department']);

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Rafraîchir le token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Révoquer le token actuel
        $request->user()->currentAccessToken()->delete();
        
        // Créer un nouveau token
        $deviceName = 'GED-Web-' . substr(md5($request->userAgent()), 0, 8);
        $token = $user->createToken($deviceName, ['*'], now()->addHours(8));

        $this->auditService->log(
            action: 'token_refresh',
            modelType: 'User',
            modelId: $user->id,
            details: [
                'ip_address' => $request->ip(),
            ],
            severity: 'info'
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    /**
     * Vérifier le mot de passe (pour signatures électroniques)
     * Conforme 21 CFR Part 11 - Vérification avant signature
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            $this->auditService->log(
                action: 'password_verification_failed',
                modelType: 'User',
                modelId: $user->id,
                details: [
                    'ip_address' => $request->ip(),
                    'context' => 'electronic_signature',
                ],
                severity: 'warning'
            );

            return response()->json([
                'valid' => false,
                'message' => 'Mot de passe incorrect.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Mot de passe vérifié.',
        ]);
    }

    /**
     * Vérifier le PIN (pour signatures électroniques si configuré)
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $user = $request->user();

        if (!$user->signature_pin || !Hash::check($request->pin, $user->signature_pin)) {
            $this->auditService->log(
                action: 'pin_verification_failed',
                modelType: 'User',
                modelId: $user->id,
                details: [
                    'ip_address' => $request->ip(),
                    'context' => 'electronic_signature',
                ],
                severity: 'warning'
            );

            return response()->json([
                'valid' => false,
                'message' => 'PIN incorrect.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'PIN vérifié.',
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:12|confirmed|different:current_password',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 422);
        }

        // Vérifier que le nouveau mot de passe n'est pas dans l'historique
        // (conformité 21 CFR Part 11 - pas de réutilisation des 12 derniers mots de passe)
        // Cette logique nécessiterait une table password_history

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        // Révoquer tous les autres tokens
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        $this->auditService->log(
            action: 'password_changed',
            modelType: 'User',
            modelId: $user->id,
            details: [
                'ip_address' => $request->ip(),
            ],
            severity: 'info'
        );

        return response()->json([
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }

    /**
     * Configurer le PIN de signature
     */
    public function setSignaturePin(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'pin' => 'required|string|size:4|regex:/^[0-9]+$/',
        ]);

        $user = $request->user();

        // Vérifier le mot de passe avant de changer le PIN
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect.',
            ], 422);
        }

        $user->update([
            'signature_pin' => Hash::make($request->pin),
        ]);

        $this->auditService->log(
            action: 'signature_pin_configured',
            modelType: 'User',
            modelId: $user->id,
            details: [
                'ip_address' => $request->ip(),
            ],
            severity: 'info'
        );

        return response()->json([
            'message' => 'PIN de signature configuré avec succès.',
        ]);
    }

    /**
     * Récupérer les permissions de l'utilisateur
     */
    public function permissions(Request $request)
    {
        $user = $request->user();
        $user->load('roles.permissions');

        $permissions = $user->roles
            ->flatMap(fn($role) => $role->permissions)
            ->unique('id')
            ->pluck('slug')
            ->values();

        return response()->json([
            'permissions' => $permissions,
            'roles' => $user->roles->pluck('slug'),
        ]);
    }

    /**
     * Formater les données utilisateur pour l'API
     */
    protected function formatUser(User $user): array
    {
        $permissions = [];
        $roles = [];

        if ($user->relationLoaded('roles')) {
            $roles = $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ])->values();

            $permissions = $user->roles
                ->flatMap(fn($role) => $role->permissions ?? [])
                ->unique('id')
                ->pluck('name')  // Use 'name' instead of 'slug' - permissions use 'name' field
                ->values();
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id ?? null,
            'title' => $user->title ?? null,
            'department' => $user->relationLoaded('department') && $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
            ] : null,
            'roles' => $roles,
            'permissions' => $permissions,
            'has_signature_pin' => !empty($user->signature_pin),
            'training_completed' => $user->training_completed_at !== null,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'password_expires_at' => $user->password_changed_at
                ? $user->password_changed_at->addDays(config('ged.password_max_age_days', 90))->toIso8601String()
                : null,
        ];
    }
}
