<?php

/**
 * GED API Test Script
 * Tests all API endpoints and reports errors
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

$baseUrl = 'http://localhost:8000/api/ged';
$results = [];
$errors = [];
$token = null;

// Helper function to log results
function logResult($endpoint, $method, $status, $success, $error = null) {
    global $results, $errors;
    $result = [
        'endpoint' => $endpoint,
        'method' => $method,
        'status' => $status,
        'success' => $success,
        'error' => $error
    ];
    $results[] = $result;
    if (!$success) {
        $errors[] = $result;
    }
    $icon = $success ? '✓' : '✗';
    echo "[$icon] $method $endpoint - Status: $status" . ($error ? " - Error: $error" : "") . "\n";
}

// Test users with different roles
$testUsers = [
    ['email' => 'admin@ged-pharma.local', 'password' => 'Admin@GED2024!', 'role' => 'Admin'],
    ['email' => 'sophie.martin@ged-pharma.local', 'password' => 'Test@GED2024!', 'role' => 'QA Manager'],
    ['email' => 'pierre.dubois@ged-pharma.local', 'password' => 'Test@GED2024!', 'role' => 'QA Analyst'],
];

echo "\n========================================\n";
echo "  GED Pharma API Test Suite\n";
echo "========================================\n\n";

foreach ($testUsers as $testUser) {
    echo "=== Testing as {$testUser['role']} ({$testUser['email']}) ===\n\n";
    
    // Test 1: Login
    echo "--- Authentication Tests ---\n";
    try {
        $response = Http::post("$baseUrl/auth/login", [
            'email' => $testUser['email'],
            'password' => $testUser['password']
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $token = $data['token'] ?? null;
            logResult('/auth/login', 'POST', $response->status(), true);
        } else {
            logResult('/auth/login', 'POST', $response->status(), false, $response->json()['message'] ?? 'Unknown error');
            continue; // Skip to next user if login fails
        }
    } catch (\Exception $e) {
        logResult('/auth/login', 'POST', 500, false, $e->getMessage());
        continue;
    }

    if (!$token) {
        echo "\n[WARN] Cannot proceed without authentication token for {$testUser['role']}\n\n";
        continue;
    }

    // Helper for authenticated requests
    $apiGet = function($endpoint) use ($baseUrl, $token) {
        return Http::withToken($token)->accept('application/json')->get("$baseUrl$endpoint");
    };

    $apiPost = function($endpoint, $data = []) use ($baseUrl, $token) {
        return Http::withToken($token)->accept('application/json')->post("$baseUrl$endpoint", $data);
    };

    $apiPut = function($endpoint, $data = []) use ($baseUrl, $token) {
        return Http::withToken($token)->accept('application/json')->put("$baseUrl$endpoint", $data);
    };

    // Test authenticated endpoints
    echo "\n--- User Profile Tests ---\n";

    // /auth/me
    try {
        $response = $apiGet('/auth/me');
        logResult('/auth/me', 'GET', $response->status(), $response->successful(), 
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/auth/me', 'GET', 500, false, $e->getMessage());
    }

    // /auth/permissions
    try {
        $response = $apiGet('/auth/permissions');
        logResult('/auth/permissions', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/auth/permissions', 'GET', 500, false, $e->getMessage());
    }

    // /auth/activities
    try {
        $response = $apiGet('/auth/activities');
        logResult('/auth/activities', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/auth/activities', 'GET', 500, false, $e->getMessage());
    }

    echo "\n--- Dashboard Tests ---\n";

    // /dashboard
    try {
        $response = $apiGet('/dashboard');
        logResult('/dashboard', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/dashboard', 'GET', 500, false, $e->getMessage());
    }

    // /dashboard/stats
    try {
        $response = $apiGet('/dashboard/stats');
        logResult('/dashboard/stats', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/dashboard/stats', 'GET', 500, false, $e->getMessage());
    }

    echo "\n--- Documents Tests ---\n";

    // /documents
    try {
        $response = $apiGet('/documents');
        logResult('/documents', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/documents', 'GET', 500, false, $e->getMessage());
    }

    // /documents/categories
    try {
        $response = $apiGet('/documents/categories');
        logResult('/documents/categories', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/documents/categories', 'GET', 500, false, $e->getMessage());
    }

    // /documents/types
    try {
        $response = $apiGet('/documents/types');
        logResult('/documents/types', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/documents/types', 'GET', 500, false, $e->getMessage());
    }

    // /documents/statuses
    try {
        $response = $apiGet('/documents/statuses');
        logResult('/documents/statuses', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/documents/statuses', 'GET', 500, false, $e->getMessage());
    }

    // /documents/needing-review
    try {
        $response = $apiGet('/documents/needing-review');
        logResult('/documents/needing-review', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/documents/needing-review', 'GET', 500, false, $e->getMessage());
    }

    echo "\n--- Workflows Tests ---\n";

    // /workflows/definitions
    try {
        $response = $apiGet('/workflows/definitions');
        logResult('/workflows/definitions', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/workflows/definitions', 'GET', 500, false, $e->getMessage());
    }

    // /workflows/my-pending
    try {
        $response = $apiGet('/workflows/my-pending');
        logResult('/workflows/my-pending', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/workflows/my-pending', 'GET', 500, false, $e->getMessage());
    }

    echo "\n--- Audit Tests ---\n";

    // /audit
    try {
        $response = $apiGet('/audit');
        logResult('/audit', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/audit', 'GET', 500, false, $e->getMessage());
    }

    // /audit/statistics
    try {
        $response = $apiGet('/audit/statistics');
        logResult('/audit/statistics', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/audit/statistics', 'GET', 500, false, $e->getMessage());
    }

    // /audit/verify-integrity
    try {
        $response = $apiGet('/audit/verify-integrity');
        logResult('/audit/verify-integrity', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/audit/verify-integrity', 'GET', 500, false, $e->getMessage());
    }

    // /audit/export
    try {
        $response = $apiGet('/audit/export?format=json');
        logResult('/audit/export', 'GET', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/audit/export', 'GET', 500, false, $e->getMessage());
    }

    // Logout
    echo "\n--- Logout Test ---\n";
    try {
        $response = $apiPost('/auth/logout');
        logResult('/auth/logout', 'POST', $response->status(), $response->successful(),
                  $response->failed() ? ($response->json()['message'] ?? 'Failed') : null);
    } catch (\Exception $e) {
        logResult('/auth/logout', 'POST', 500, false, $e->getMessage());
    }

    echo "\n";
}

// Summary
echo "\n========================================\n";
echo "  Test Summary\n";
echo "========================================\n";
$passed = count(array_filter($results, fn($r) => $r['success']));
$failed = count($errors);
echo "Total tests: " . count($results) . "\n";
echo "Passed: " . $passed . "\n";
echo "Failed: " . $failed . "\n";
echo "Success rate: " . round(($passed / count($results)) * 100, 1) . "%\n";

if (count($errors) > 0) {
    echo "\n--- Failed Endpoints ---\n";
    foreach ($errors as $error) {
        echo "  - {$error['method']} {$error['endpoint']}: {$error['error']}\n";
    }
}

// Save results to JSON
file_put_contents('test-results.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'total' => count($results),
    'passed' => $passed,
    'failed' => $failed,
    'success_rate' => round(($passed / count($results)) * 100, 1),
    'results' => $results,
    'errors' => $errors
], JSON_PRETTY_PRINT));

echo "\nResults saved to test-results.json\n";
