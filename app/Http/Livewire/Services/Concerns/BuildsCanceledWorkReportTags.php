<?php

namespace App\Http\Livewire\Services\Concerns;

use Illuminate\Support\Facades\DB;

trait BuildsCanceledWorkReportTags
{
    /**
     * Informe cancelado vinculado diretamente a cada produção (uma query por página).
     *
     * @return array<int, array{id:int, canceled_at:?string}>
     */
    protected function buildCanceledWorkReportByProductionIds(array $productionIds): array
    {
        $productionIds = collect($productionIds)->filter()->unique()->values()->all();

        if ($productionIds === []) {
            return [];
        }

        return DB::table('work_report_flow_productions as wrfp')
            ->join('work_reports as wr', 'wr.id', '=', 'wrfp.work_report_id')
            ->whereIn('wrfp.production_id', $productionIds)
            ->where('wrfp.is_current', true)
            ->where('wr.canceled', true)
            ->orderBy('wr.canceled_at')
            ->get(['wrfp.production_id', 'wr.id', 'wr.canceled_at'])
            ->unique('production_id')
            ->mapWithKeys(fn ($row) => [
                (int) $row->production_id => [
                    'id'          => (int) $row->id,
                    'canceled_at' => $row->canceled_at ? date('d/m/Y H:i', strtotime($row->canceled_at)) : null,
                ],
            ])
            ->all();
    }

    /** Ids das produções deste serviço já carregadas nas notas da lista. */
    protected function serviceProductionIds(iterable $notes, string $serviceUuid): array
    {
        return collect($notes)
            ->flatMap(fn ($note) => $note->relationLoaded('Productions')
                ? $note->Productions->where('service_id', $serviceUuid)->pluck('id')
                : collect())
            ->all();
    }
}
