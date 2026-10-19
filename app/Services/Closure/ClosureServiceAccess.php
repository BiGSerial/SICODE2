<?php

namespace App\Services\Closure;

use App\Models\{ServiceUser, User};

/**
 * Vínculo do usuário com o Service Encerramento (tabela service_users), a mesma regra de acesso
 * dos demais serviços. Regra dos gates de domínio (ver AuthServiceProvider):
 *
 *  - service_users.dispatch  => closure.dispatch, closure.manage e closure.view
 *  - service_users.service   => closure.work e closure.view
 *
 * `closure.manager` (Visão geral / Meta / Passivo) continua exclusivo da flag closure_manager
 * (+ admin/superadm): não é concedido por vínculo de serviço.
 */
class ClosureServiceAccess
{
    public const DISPATCH = 'dispatch';

    public const SERVICE = 'service';

    public static function has(User $user, string $column): bool
    {
        if (!in_array($column, [self::DISPATCH, self::SERVICE], true)) {
            return false;
        }

        return ServiceUser::query()
            ->where('user_id', $user->id)
            ->where($column, true)
            ->whereHas('Service', fn ($service) => $service->where('folder', ClosureServiceLocator::FOLDER))
            ->exists();
    }
}
