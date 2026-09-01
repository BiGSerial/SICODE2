<?php

namespace App\Services\Closure;

use App\Models\{ClosureCycle, ClosureTarget, Operation, Order};
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regra de entrada na meta de encerramento (confirmada e validada com dados reais em 2026-08-30):
 * Order.statusSist LIKE 'LIB%' E Operation(operacao='0020') com status LIKE 'CONF%' E fimReal preenchido,
 * com fimReal caindo no mês imediatamente anterior à competência (mensalização).
 */
class ClosureTargetFreezer
{
    public const ENTRY_RULE = 'lib_op20_conf_fimreal_v1';

    public function eligibleOrders(Carbon $referenceStart, Carbon $referenceEnd): Collection
    {
        return Order::query()
            ->where('statusSist', 'like', 'LIB%')
            ->where('canceled', false)
            ->whereDoesntHave('ClosureTarget')
            ->whereHas('Note', fn ($query) => $query->excludeCanceledFullDone())
            ->whereHas('Operations', function ($query) use ($referenceStart, $referenceEnd) {
                $query->where('operacao', '0020')
                    ->where('status', 'like', 'CONF%')
                    ->whereNotNull('fimReal')
                    ->whereBetween('fimReal', [$referenceStart->toDateString(), $referenceEnd->toDateString()]);
            })
            ->get();
    }

    public function matchingOperation(Order $order, Carbon $referenceStart, Carbon $referenceEnd): ?Operation
    {
        return Operation::query()
            ->where('order_id', $order->id)
            ->where('operacao', '0020')
            ->where('status', 'like', 'CONF%')
            ->whereNotNull('fimReal')
            ->whereBetween('fimReal', [$referenceStart->toDateString(), $referenceEnd->toDateString()])
            ->first();
    }

    public static function referenceRangeFor(int $year, int $month): array
    {
        $referenceStart = Carbon::create($year, $month, 1)->subMonthNoOverflow()->startOfMonth();
        $referenceEnd   = (clone $referenceStart)->endOfMonth();

        return [$referenceStart, $referenceEnd];
    }

    /**
     * Grava (ou simula, quando $commit=false) um snapshot da meta de uma competência (year/month),
     * a partir das Ordens elegíveis cujo fimReal caiu no mês de referência (mês anterior).
     *
     * Pode ser chamado várias vezes enquanto a competência estiver OPEN: cada execução só insere
     * as Ordens elegíveis que ainda não têm closure_target (idempotente por natureza, via
     * whereDoesntHave('ClosureTarget')) — é assim que se cobre o fluxo combinado com o usuário:
     * rodar no dia 1 (primeiro snapshot) e de novo às 0:00 do dia 2 (injeta Ordens que só
     * sincronizaram depois, por causa do lag do sync do SAP), antes de travar de vez com $lock=true.
     *
     * @param bool $lock Quando true, trava a competência (status=FROZEN) após inserir — não aceita
     *                    mais nenhuma Ordem depois disso. Quando false, a competência fica OPEN,
     *                    pronta para uma nova chamada (injeção) mais tarde.
     */
    public function freeze(int $year, int $month, bool $commit, ?string $frozenBy = null, bool $lock = false): array
    {
        [$referenceStart, $referenceEnd] = self::referenceRangeFor($year, $month);

        $label          = sprintf('%04d-%02d', $year, $month);
        $eligibleOrders = $this->eligibleOrders($referenceStart, $referenceEnd);

        $result = [
            'label'           => $label,
            'reference_start' => $referenceStart,
            'reference_end'   => $referenceEnd,
            'orders'          => $eligibleOrders,
            'created'         => 0,
            'cycle'           => null,
            'already_frozen'  => false,
            'locked_now'      => false,
        ];

        if (!$commit) {
            return $result;
        }

        $cycle = ClosureCycle::firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['label' => $label, 'status' => ClosureCycle::STATUS_OPEN]
        );

        $result['cycle'] = $cycle;

        if ($cycle->status === ClosureCycle::STATUS_FROZEN) {
            $result['already_frozen'] = true;

            return $result;
        }

        $created = 0;

        DB::transaction(function () use ($eligibleOrders, $cycle, $referenceStart, $referenceEnd, $frozenBy, $lock, &$created) {
            foreach ($eligibleOrders as $order) {
                $operation = $this->matchingOperation($order, $referenceStart, $referenceEnd);

                ClosureTarget::create([
                    'closure_cycle_id' => $cycle->id,
                    'order_id'         => $order->id,
                    'note_id'          => $order->note_id,
                    'entry_rule'       => self::ENTRY_RULE,
                    'entry_reference'  => [
                        'operation_id'     => $operation?->id,
                        'fim_real'         => $operation?->fimReal?->toDateString(),
                        'operation_status' => $operation?->status,
                    ],
                    'snapshot_status_sist' => $order->statusSist,
                    'frozen_at'            => now(),
                ]);

                $created++;
            }

            if ($lock) {
                $cycle->update([
                    'status'    => ClosureCycle::STATUS_FROZEN,
                    'frozen_at' => now(),
                    'frozen_by' => $frozenBy,
                ]);
            }
        });

        $result['created']    = $created;
        $result['locked_now'] = $lock;

        return $result;
    }
}
