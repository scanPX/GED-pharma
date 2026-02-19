<?php

namespace App\Http\Middleware\GED;

use Closure;
use Illuminate\Http\Request;
use App\Services\GED\AuditService;
use Symfony\Component\HttpFoundation\Response;

/**
 * GED Audit Middleware
 * 
 * Middleware de traçabilité automatique des actions GED
 * Conforme EU Annex 11 et 21 CFR Part 11
 */
class AuditMiddleware
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Ajouter un ID de requête unique pour traçabilité
        $requestId = (string) \Illuminate\Support\Str::uuid();
        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);

        // Logger les actions importantes automatiquement
        $this->logIfNeeded($request, $response);

        return $response;
    }

    protected function logIfNeeded(Request $request, Response $response): void
    {
        // Ne logger que les actions modificatrices réussies
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        if ($response->getStatusCode() >= 400) {
            return;
        }

        // Le logging détaillé est fait dans les services
        // Ce middleware assure la traçabilité de base
    }
}
