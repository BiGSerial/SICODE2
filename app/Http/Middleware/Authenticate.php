<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        $user = Auth::user();
        if ($user && $user->onlyparner && !$this->isAllowedOnlyPartnerRoute($request)) {
            return redirect()->route('partner.main.viability');
        }

        return $next($request);
    }

    private function isAllowedOnlyPartnerRoute(Request $request): bool
    {
        $route = $request->route();
        $name = (string) optional($route)->getName();

        if (str_starts_with($name, 'partner.') || str_starts_with($name, 'protests.partner.')) {
            return true;
        }

        if (str_starts_with($name, 'livewire.') || str_starts_with($request->path(), 'livewire')) {
            return true;
        }

        return in_array($name, [
            'logout',
            'pdf.checklist',
            'pdf.checklistFiscal',
            'protests.print',
        ], true);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
