<?php

namespace App\Services\Dispatch;

use App\Models\{Note, Production, Service, User};
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query centralizada para atividades abertas na pilha da empresa parceira.
 *
 * A visibilidade desta fila não depende do status da Nota. Ela depende de
 * existir uma Production do serviço, pertencente a uma empresa visível para
 * o usuário, ainda sem usuário atribuído e ainda não concluída/confirmada.
 */
class PartnerStackQuery
{
    public function productions(Service|string $service, User $user): Builder
    {
        $serviceId = $service instanceof Service ? $service->uuid : (string) $service;

        $query = Production::query()
            ->where("service_id", $serviceId)
            ->whereNull("user_id")
            ->where("completed", false)
            ->where("confirmed", false);

        if (!$user->contract) {
            return $query->whereRaw("0 = 1");
        }

        $companyIds = SicodeRules::visibleCompanyIdsFor($user);

        return count($companyIds)
            ? $query->whereIn("company_id", $companyIds)
            : $query->whereRaw("0 = 1");
    }

    public function notes(Service|string $service, User $user): Builder
    {
        $serviceId = $service instanceof Service ? $service->uuid : (string) $service;

        if (!$user->contract) {
            return Note::query()->whereRaw('0 = 1');
        }

        $companyIds = SicodeRules::visibleCompanyIdsFor($user);

        return Note::query()->whereHas("Productions", function ($query) use ($serviceId, $companyIds) {
            $query->where("service_id", $serviceId)
                ->whereNull("user_id")
                ->where("completed", false)
                ->where("confirmed", false)
                ->whereIn("company_id", $companyIds);
        });
    }

    /**
     * Todas as atividades encaminhadas para as empresas do parceiro,
     * independentemente do status ou de já terem sido assumidas/finalizadas.
     */
    public function dispatched(Service|string $service, User $user): Builder
    {
        $serviceId = $service instanceof Service ? $service->uuid : (string) $service;

        $query = Production::query()
            ->where('service_id', $serviceId)
            ->whereNotNull('dispatch_at');

        if (!$user->contract) {
            return $query->whereRaw('0 = 1');
        }

        $companyIds = SicodeRules::visibleCompanyIdsFor($user);

        return count($companyIds)
            ? $query->whereIn('company_id', $companyIds)
            : $query->whereRaw('0 = 1');
    }

    public function openFor(Note $note, Service|string $service, User $user): ?Production
    {
        if (!$user->contract) {
            return null;
        }

        return $this->productions($service, $user)
            ->where("note_id", $note->id)
            ->orderByDesc("id")
            ->first();
    }
}
