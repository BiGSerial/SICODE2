<?php

namespace App\Services\Menu;

use App\Http\Livewire\Components\Count\CancellationRequests;
use App\Http\Livewire\Engineers\Counts\{CountParcial, PepAlertsCount};
use App\Models\{Production, ProtestJob, Service, User};

/**
 * Contadores do menu superior, todos de uma vez (antes cada item era um componente Livewire com o próprio `wire:poll`: 15+ requisições
 * por ciclo). Chave => número; o JS (`resources/js/nav/topbar.js`) preenche o `[data-tb-count="chave"]` de cada item.
 *
 * Chaves: `service:{uuid}` (minhas atividades abertas), `protests` (reclamações abertas minhas), `engineer.informes`, `engineer.cancellations`.
 */
class TopbarCounts
{
    /** @return array<string, int> */
    public function for(User $user): array
    {
        $counts = $this->services($user);

        $counts['protests'] = $this->protests($user);

        if ($user->can('engineer')) {
            $counts['engineer.informes']      = (new CountParcial())->getCountProperty() + (new PepAlertsCount())->getCountProperty();
            $counts['engineer.cancellations'] = $this->engineerCancellations($user);
        }

        return $counts;
    }

    /**
     * Atividades abertas do usuário, por atividade, numa consulta só. Regra do Desenho: atribuídas e abertas, ou devolvidas pela
     * análise de projeto; as demais: qualquer uma ainda não concluída.
     *
     * @return array<string, int>
     */
    private function services(User $user): array
    {
        $design = Service::query()->where('folder', 'desenho')->pluck('uuid')->all();

        $rows = Production::query()
            ->where('user_id', $user->id)
            ->selectRaw(
                'service_id, SUM(CASE WHEN ' . ($design ? 'service_id IN (' . implode(',', array_fill(0, count($design), '?')) . ')' : '0 = 1') . '
                    THEN (CASE WHEN (completed = 0 AND status = 2) OR status = ? THEN 1 ELSE 0 END)
                    ELSE (CASE WHEN completed = 0 THEN 1 ELSE 0 END) END) as total',
                [...$design, Production::STATUS_REJECTED_PROJECT_REVIEW]
            )
            ->groupBy('service_id')
            ->pluck('total', 'service_id');

        return $rows->mapWithKeys(fn ($total, $service) => ['service:' . $service => (int) $total])->all();
    }

    private function protests(User $user): int
    {
        return ProtestJob::query()->where('owner_id', $user->id)->whereNull('closed_at')->count();
    }

    private function engineerCancellations(User $user): int
    {
        $component         = new CancellationRequests();
        $component->mode   = 'engineer_pending';
        $component->userId = (string) $user->id;

        return $component->getCountProperty();
    }
}
