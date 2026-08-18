<?php

namespace App\Http\Middleware;

use App\Services\PartnerAccess\PartnerAccessGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerPermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        abort_unless(PartnerAccessGate::allows($request->user(), $permissionKey), 403);

        return $next($request);
    }
}
