<?php

namespace App\Http\Middleware;

use App\Models\{ApplicationApiAudit, ApplicationApiToken};
use Closure;
use Illuminate\Http\Request;
use Throwable;

class AuditApplicationApi
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->store($request, 500, ['error_message' => $exception->getMessage()]);
            throw $exception;
        }

        $this->store($request, $response->getStatusCode());

        return $response;
    }

    private function store(Request $request, int $status, array $extra = []): void
    {
        $token = $request->attributes->get('application_api_token');
        $summary = $request->attributes->get('application_api_summary', []);

        ApplicationApiAudit::create([
            'application_api_token_id' => $token?->id,
            'created_by_user_id' => $token?->created_by_user_id,
            'endpoint' => '/' . ltrim($request->path(), '/'),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 65535) ?: null,
            'http_status' => $status,
            'payload_hash' => hash('sha256', (string) $request->getContent()),
            'records_received' => (int) ($summary['records_received'] ?? 0),
            'records_created' => (int) ($summary['records_created'] ?? 0),
            'records_updated' => (int) ($summary['records_updated'] ?? 0),
            'operations_created' => (int) ($summary['operations_created'] ?? 0),
            'operations_updated' => (int) ($summary['operations_updated'] ?? 0),
            'error_message' => $extra['error_message'] ?? ($summary['error_message'] ?? null),
            'summary' => $summary ?: null,
        ]);
    }
}
