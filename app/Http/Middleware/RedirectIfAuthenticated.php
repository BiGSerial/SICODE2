<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        $partnerViewOnly = false;

        if (Auth::check()) {

            // dd(Auth()->User());

            $user = User::with('Employee.Contract')->find(Auth()->User()->id);

            if ($user->Employee->Contract->construction && !$user->Employee->Contract->service) {
                $partnerViewOnly = true;
            }
        }



        if (Auth::guard($guards[0])->check()) {

            if ($partnerViewOnly) {
                return redirect(RouteServiceProvider::COMPANY);
            } else {
                return redirect(RouteServiceProvider::HOME);
            }

        }

        return $next($request);
    }
}
