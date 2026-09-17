<?php

namespace App\Http\Middleware;

use App\Models\ApplicationApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApplicationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();
        $token = $plainToken
            ? ApplicationApiToken::query()
                ->where('active', true)
                ->whereNull('revoked_at')
                ->where('token_hash', hash('sha256', $plainToken))
                ->first()
            : null;

        if (!$token) {
            return response()->json(['message' => 'Bearer token inválido ou ausente.'], 401);
        }

        $token->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
        ])->saveQuietly();

        $request->attributes->set('application_api_token', $token);

        return $next($request);
    }
}
