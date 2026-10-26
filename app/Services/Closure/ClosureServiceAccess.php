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
 *
 * Caixas de pendência (Medição/Fiscalização): `closure.inbox.{area}` = vínculo (despacho OU serviço) com o Service da área,
 * ou gestor/admin/superadm. `closure.inbox.{area}.respond` hoje é igual ao de visualizar; gate separado para poder restringir
 * depois sem tocar nas telas (a aba "Em acompanhamento" é sempre só visão).
 */
class ClosureServiceAccess
{
    public const DISPATCH = 'dispatch';

    public const SERVICE = 'service';

    public static function has(User $user, string $column): bool
    {
        return self::hasForFolder($user, ClosureServiceLocator::FOLDER, $column);
    }

    /** Vínculo do usuário com o Service de outra pasta (ex.: `pagamento`, `fiscalizacao`) — usado pelas caixas de pendência. */
    public static function hasForFolder(User $user, string $folder, string $column): bool
    {
        if (!in_array($column, [self::DISPATCH, self::SERVICE], true)) {
            return false;
        }

        return ServiceUser::query()
            ->where('user_id', $user->id)
            ->where($column, true)
            ->whereHas('Service', fn ($service) => $service->where('folder', $folder))
            ->exists();
    }

    /** Vínculo (despacho OU serviço) com o Service da área que tem caixa (Medição/Fiscalização). */
    public static function hasInbox(User $user, string $area): bool
    {
        $folder = ClosureArea::serviceFolder($area);

        return $folder !== null
            && (self::hasForFolder($user, $folder, self::DISPATCH) || self::hasForFolder($user, $folder, self::SERVICE));
    }
}
