<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureOrderState;
use Illuminate\Support\Facades\DB;

/**
 * Registra, por Ordem do Encerramento, desde quando ela está no status usuário atual (prefixo de 2 tokens de orders.statusUser).
 * É o que a caixa "Em acompanhamento" mostra como "no status desde…".
 *
 * Roda logo após o sync de Ordens do SAP (`sicode:upd_baseOrder` é quem grava orders.statusUser; este módulo NÃO altera essa
 * rotina nem a tabela orders). A data tem, portanto, a precisão da frequência desse sync (algumas horas) — informativo, não SLA.
 *
 *  - primeira vez que a Ordem é vista: grava o status e a data de agora, com `status_user_estimated = true`
 *    (o início real pode ser anterior; a tela mostra "desde o 1º registro");
 *  - depois, só quando o prefixo MUDA: nova data, `status_user_estimated = false`.
 * Não é uma transição operacional: não passa pelo ClosureOrderWorkflowService nem gera evento. Idempotente.
 */
class ClosureStatusUserTracker
{
    /** @return array{scanned: int, initialized: int, changed: int} */
    public function sync(): array
    {
        $summary = ['scanned' => 0, 'initialized' => 0, 'changed' => 0];

        ClosureOrderState::query()
            ->whereNotIn('operational_status', [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED])
            ->chunkById(500, function ($states) use (&$summary) {
                $current = DB::table('orders')->whereIn('id', $states->pluck('order_id'))->pluck('statusUser', 'id');

                foreach ($states as $state) {
                    $summary['scanned']++;

                    $prefix = ClosureEligibleOrders::statusUserPrefix($current[$state->order_id] ?? null);

                    if ($state->status_user_seen === null) {
                        $this->record($state, $prefix, estimated: true);
                        $summary['initialized']++;
                    } elseif ($state->status_user_seen !== $prefix) {
                        $this->record($state, $prefix, estimated: false);
                        $summary['changed']++;
                    }
                }
            });

        return $summary;
    }

    private function record(ClosureOrderState $state, string $prefix, bool $estimated): void
    {
        ClosureOrderState::query()->whereKey($state->id)->update([
            'status_user_seen'       => $prefix,
            'status_user_changed_at' => now(),
            'status_user_estimated'  => $estimated,
        ]);
    }
}
