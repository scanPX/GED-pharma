<?php

namespace App\Http\Controllers\GED\Admin;

use App\Http\Controllers\Controller;
use App\Models\GED\SystemSetting;
use App\Services\GED\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get all system settings.
     */
    public function index(Request $request): JsonResponse
    {
           // Require the dedicated configuration permission
           if (!($request->user()->can('system.configure') || $request->user()->can('user.manage'))) {
               return response()->json(['message' => 'Unauthorized'], 403);
           }

        // Ensure default settings exist
        $defaults = [
            'security.password_expiry_days' => ['value' => '90', 'group' => 'security', 'desc' => 'Jours avant expiration du mot de passe'],
            'security.session_timeout_minutes' => ['value' => '30', 'group' => 'security', 'desc' => 'Durée de session inavtive (min)'],
            'audit.retention_period_years' => ['value' => '10', 'group' => 'audit', 'desc' => 'Durée de conservation des logs (années)'],
            'audit.reason_required' => ['value' => '1', 'group' => 'audit', 'desc' => 'Exiger une raison pour les actions critiques'],
        ];

        foreach ($defaults as $key => $data) {
            SystemSetting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $data['value'],
                    'group' => $data['group'],
                    'description' => $data['desc'],
                    'is_encrypted' => false
                ]
            );
        }

        $settings = SystemSetting::all()->groupBy('group')
            ->map(function ($group) {
                return $group->values(); // ensure numeric arrays for JSON
            });

        return response()->json([
            'settings' => $settings
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request): JsonResponse
    {
           if (!($request->user()->can('system.configure') || $request->user()->can('user.manage'))) {
               return response()->json(['message' => 'Unauthorized'], 403);
           }

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'exists:ged_system_settings,key'],
            'settings.*.value' => ['nullable'],
        ]);

        DB::beginTransaction();
        try {
            $changes = [];

            foreach ($validated['settings'] as $item) {
                $setting = SystemSetting::where('key', $item['key'])->first();
                if ($setting) {
                     $oldValue = $setting->value;
                     // Normalize boolean-like values
                     $newValue = $item['value'];
                     if (is_string($newValue) && ($newValue === '0' || $newValue === '1')) {
                         // keep as string but comparison should be loose to avoid false positives
                     }

                     if ((string)$oldValue !== (string)$newValue) {
                         $setting->value = $newValue; // Mutator handles encryption if set
                         $setting->save();
                         $changes[$item['key']] = ['old' => $oldValue, 'new' => $newValue];
                     }
                }
            }

            if (!empty($changes)) {
                $this->auditService->log(
                    'config_updated',
                    'system',
                    "Configuration système mise à jour",
                    null,
                    ['changes' => array_keys($changes)], // Don't log values if sensitive?
                    ['changes' => $changes]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully.',
                'settings' => SystemSetting::all()->groupBy('group')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
