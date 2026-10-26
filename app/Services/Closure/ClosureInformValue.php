<?php

namespace App\Services\Closure;

use Illuminate\Support\Facades\DB;

/**
 * Valor da obra a que a Ordem pertence = "Valor ADS" do INFORME (a mesma coluna "Valor ADS" da Lista para Medição).
 *
 * O informe da Ordem é resolvido pela mesma regra da empresa do informe (ClosureEligibleOrders::informCompanies), parando na primeira que existir
 * (empate = registro mais recente, maior id):
 *  1. informe não cancelado ligado à Ordem (`order_work_report`)  -> `adsforms.amount` do informe; sem ADS próprio, a ADS da Nota;
 *  2. parcial ligada à Ordem (`order_partial`)                    -> `partials.value` (valor da parcial);
 *  3. informe não cancelado da Nota da Ordem                      -> como em 1.
 * Sem informe/parcial, ou sem valor, o resultado é null (a tela mostra "—").
 */
final class ClosureInformValue
{
    /**
     * @param iterable<int, int|string|null> $orderIds
     *
     * @return array<int, float|null> order_id => valor (R$) ou null
     */
    public function forOrders(iterable $orderIds): array
    {
        $ids = [];

        foreach ($orderIds as $id) {
            if ($id !== null && (int) $id > 0) {
                $ids[(int) $id] = (int) $id;
            }
        }

        $result = array_fill_keys(array_values($ids), null);

        foreach (array_chunk(array_values($ids), 1000) as $chunk) {
            $result = $this->resolve($chunk) + $result;
        }

        return $result;
    }

    /** "R$ 12.345,67" ou "—" quando não há valor. */
    public static function format(?float $value): string
    {
        return $value === null ? '—' : 'R$ ' . number_format($value, 2, ',', '.');
    }

    /**
     * @param array<int, int> $chunk
     *
     * @return array<int, float|null>
     */
    private function resolve(array $chunk): array
    {
        $found = [];

        $sources = [
            fn (array $in) => DB::table('order_work_report as p')->join('work_reports as w', 'w.id', '=', 'p.work_report_id')
                ->whereIn('p.order_id', $in)->where('w.canceled', false)->whereNotNull('w.company_id')
                ->orderBy('w.id')->get(['p.order_id as oid', 'w.id as wid', 'w.note_id as nid', DB::raw('null as pvalue')]),
            fn (array $in) => DB::table('order_partial as p')->join('partials as pt', 'pt.id', '=', 'p.partial_id')
                ->whereIn('p.order_id', $in)->whereNotNull('pt.company_id')
                ->orderBy('pt.id')->get(['p.order_id as oid', DB::raw('null as wid'), DB::raw('null as nid'), 'pt.value as pvalue']),
            fn (array $in) => DB::table('orders as o')->join('work_reports as w', 'w.note_id', '=', 'o.note_id')
                ->whereIn('o.id', $in)->where('w.canceled', false)->whereNotNull('w.company_id')
                ->orderBy('w.id')->get(['o.id as oid', 'w.id as wid', 'w.note_id as nid', DB::raw('null as pvalue')]),
        ];

        foreach ($sources as $source) {
            $pending = array_values(array_diff($chunk, array_keys($found)));

            if ($pending === []) {
                break;
            }

            foreach ($source($pending) as $row) {
                $found[(int) $row->oid] = $row; // ordenado por id asc: o mais recente sobrescreve
            }
        }

        $workReportIds = collect($found)->pluck('wid')->filter()->unique()->values()->all();
        $noteIds       = collect($found)->pluck('nid')->filter()->unique()->values()->all();

        $byWorkReport = $workReportIds === [] ? [] : DB::table('adsforms')->whereIn('work_report_id', $workReportIds)->whereNotNull('amount')->orderBy('id')->pluck('amount', 'work_report_id')->all();
        $byNote       = $noteIds === [] ? [] : DB::table('adsforms')->whereIn('note_id', $noteIds)->whereNotNull('amount')->orderBy('id')->pluck('amount', 'note_id')->all();

        $values = [];

        foreach ($found as $orderId => $row) {
            $amount = $row->pvalue ?? ($byWorkReport[$row->wid] ?? $byNote[$row->nid] ?? null);

            $values[$orderId] = $amount === null ? null : (float) $amount;
        }

        return $values;
    }
}
