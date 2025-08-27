<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckServiceOrDispatchPermission
{
    public function handle(Request $request, Closure $next, $type)
    {

        // if (Auth::user()->superadm) {
        //     return $next($request);
        // }



        if (!Auth::check()) {
            return redirect('/');
        }

        $user = Auth::user();


        $serviceId = $request->route('service');
        $type = $request->segment(1);



        //Verifica se o usuário é SUPERADM
        if ($user?->superadm) {
            return $next($request);
        }


        if ($user?->ToServices->isEmpty()) {
            abort(403, 'Você não tem permissão para realizar nenhum serviço ou despacho no sistema. Por favor, entre em contato com o administrador do SICODE ou seu Responsável.');
        }


        // Busca o relacionamento de serviços do usuário
        $servicePermission = $user?->ToServices->firstWhere('service_id', $serviceId);

        if (!$servicePermission) {
            // Se não há serviço correspondente, bloqueia
            abort(403, 'Você não tem permissão para realizar serviço em '.$servicePermission?->Service->service);
        }

        // Verifica a permissão com base no tipo de rota (services, construction, dispatch)
        if ($type == 'dispatch' && !$servicePermission?->dispatch) {
            abort(403, 'Você não tem permissão para despacho em '.$servicePermission?->Service->service);
        }

        if (($type == 'services' || $type == 'construction') && !$servicePermission->service) {
            abort(403, 'Você não tem permissão para serviço em '.$servicePermission->Service->service);
        }

        // Se todas as condições forem satisfeitas, permite o acesso
        return $next($request);
    }
}
