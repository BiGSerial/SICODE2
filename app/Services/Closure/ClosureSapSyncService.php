<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureOrderState;
use Illuminate\Database\Eloquent\Builder;

/**
 * Detecta, após a sincronização do SAP, Ordens do Encerramento encerradas (ENTE/ENCE) ou canceladas,
 * movendo a atividade para CLOSED/CANCELLED e congelando o tipo de meta. Idempotente.
 */
class ClosureSapSyncService
{
    public function __construct(private ?ClosureOrderWorkflowService $workflow = null)
    {
        $this->workflow ??= app(ClosureOrderWorkflowService::class);
    }

    /** @return array{scanned: int, closed: int, cancelled: int} */
    public function syncClosed(): array
    {
        $summary = ['scanned' => 0, 'closed' => 0, 'cancelled' => 0];

        ClosureOrderState::query()
            ->whereNotIn('operational_status', [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED])
            ->whereHas('Order', fn (Builder $order) => $order
                ->where('canceled', true)
                ->orWhere('statusSist', 'like', 'ENTE%')
                ->orWhere('statusSist', 'like', 'ENCE%'))
            ->chunkById(200, function ($states) use (&$summary) {
                foreach ($states as $state) {
                    $summary['scanned']++;

                    match ($this->workflow->syncFromSap($state)) {
                        ClosureOrderWorkflowService::STATUS_CLOSED    => $summary['closed']++,
                        ClosureOrderWorkflowService::STATUS_CANCELLED => $summary['cancelled']++,
                        default                                       => null,
                    };
                }
            });

        return $summary;
    }
}
