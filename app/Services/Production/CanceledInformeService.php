<?php

namespace App\Services\Production;

use App\Models\{CancellationRequest, Notetimeline, Production, User, WorkReport};
use Illuminate\Support\Facades\DB;

/**
 * Produção cujo informe foi cancelado: o informe não existe mais, então a atividade não tem o que executar. O usuário só pode
 * FINALIZAR; o sistema encerra a produção, registra na análise `FINALIZADO POR CANCELAMENTO` e descreve quem cancelou e quando.
 *
 * O informe cancelado de uma produção é o vinculado a ela pelo fluxo (`work_report_flow_productions`, vínculo atual); sem vínculo, é o
 * último informe cancelado da Nota, desde que a Nota não tenha nenhum informe ativo.
 */
class CanceledInformeService
{
    public const CONCLUSION = 'FINALIZADO POR CANCELAMENTO';

    /**
     * @param array<int, int|string> $productionIds
     *
     * @return array<int, array{id: int, canceled_at: ?string, canceled_at_raw: ?string, canceled_by: ?string, orders: array<int, string>}>
     */
    public function forProductions(array $productionIds): array
    {
        $productionIds = collect($productionIds)->filter()->unique()->values()->all();

        if ($productionIds === []) {
            return [];
        }

        $linked = DB::table('work_report_flow_productions as wrfp')
            ->join('work_reports as wr', 'wr.id', '=', 'wrfp.work_report_id')
            ->whereIn('wrfp.production_id', $productionIds)
            ->where('wrfp.is_current', true)
            ->where('wr.canceled', true)
            ->orderBy('wr.canceled_at')
            ->get(['wrfp.production_id', 'wr.id'])
            ->unique('production_id')
            ->mapWithKeys(fn ($row) => [(int) $row->production_id => (int) $row->id]);

        $missing = collect($productionIds)->map(fn ($id) => (int) $id)->diff($linked->keys());

        $byNote = $missing->isEmpty() ? collect() : DB::table('productions as p')
            ->join('work_reports as wr', 'wr.note_id', '=', 'p.note_id')
            ->whereIn('p.id', $missing->all())
            ->where('wr.canceled', true)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('work_reports as active')
                ->whereColumn('active.note_id', 'p.note_id')->where('active.canceled', false))
            ->orderByDesc('wr.canceled_at')
            ->get(['p.id as production_id', 'wr.id'])
            ->unique('production_id')
            ->mapWithKeys(fn ($row) => [(int) $row->production_id => (int) $row->id]);

        $reportIds = $linked->union($byNote);

        if ($reportIds->isEmpty()) {
            return [];
        }

        $reports = WorkReport::query()
            ->whereIn('id', $reportIds->unique()->all())
            ->with('Orders:id,ordem')
            ->get(['id', 'note_id', 'canceled_at', 'canceled_by'])
            ->keyBy('id');

        $users = User::withTrashed()->whereIn('id', $reports->pluck('canceled_by')->filter()->unique())->pluck('name', 'id');

        return $reportIds->map(function (int $reportId) use ($reports, $users) {
            $report = $reports[$reportId];

            return [
                'id'              => $report->id,
                'canceled_at'     => $report->canceled_at?->format('d/m/Y H:i'),
                'canceled_at_raw' => $report->canceled_at?->toDateTimeString(),
                'canceled_by'     => $report->canceled_by ? ($users[$report->canceled_by] ?? null) : null,
                'orders'          => $report->Orders->pluck('ordem')->all(),
            ];
        })->all();
    }

    public function forProduction(Production $production): ?array
    {
        return $this->forProductions([$production->id])[(int) $production->id] ?? null;
    }

    /**
     * Texto gravado na análise e na linha do tempo: o informe, quem cancelou e quando (com o motivo, quando houver solicitação concluída).
     *
     * @param array{id: int, canceled_at: ?string, canceled_by: ?string, orders: array<int, string>} $canceled
     */
    public function description(Production $production, array $canceled, User $actor): string
    {
        $parts = [
            'Informe #' . $canceled['id'] . ' cancelado'
            . ($canceled['canceled_by'] ? ' por ' . $canceled['canceled_by'] : '')
            . ($canceled['canceled_at'] ? ' em ' . $canceled['canceled_at'] : '') . '.',
        ];

        if ($canceled['orders'] !== []) {
            $parts[] = 'Ordens do informe: ' . implode(', ', $canceled['orders']) . '.';
        }

        $request = CancellationRequest::query()
            ->where('note_id', $production->note_id)
            ->where('status', CancellationRequest::STATUS_DONE)
            ->with('Category:id,name')
            ->latest('closed_at')
            ->first();

        if ($request?->Category?->name) {
            $parts[] = 'Motivo: ' . $request->Category->name . ($request->description ? ' - ' . trim((string) $request->description) : '') . '.';
        }

        $parts[] = 'Atividade finalizada por ' . $actor->name . ' em ' . now()->format('d/m/Y H:i') . ', pela opção Finalizar por cancelamento.';

        return implode(' ', $parts);
    }

    /**
     * Encerra a produção do informe cancelado. Só vale para produção aberta cujo informe está de fato cancelado.
     *
     * @throws \DomainException
     */
    public function finish(Production $production, User $actor): void
    {
        $canceled = $this->forProduction($production);

        if ($production->completed || !$canceled) {
            throw new \DomainException('Esta atividade não tem informe cancelado para finalizar.');
        }

        $info = $this->description($production, $canceled, $actor);

        DB::transaction(function () use ($production, $actor, $canceled, $info) {
            $analise = $production->Analise()->first() ?? $production->Analise()->create();
            $analise->update(['conclusion' => self::CONCLUSION, 'info' => $info]);

            $production->update([
                'status'       => 5,
                'completed_at' => now(),
                'completed'    => true,
                'confirmed'    => false,
                'priority'     => false,
            ]);

            $production->measurementContext()->update(['active_demand_key' => null]);

            Notetimeline::create([
                'note_id'       => $production->note_id,
                'service_id'    => $production->service_id,
                'user_id'       => $actor->id,
                'info'          => "Usuário {$actor->name} finalizou a Nota/OV por cancelamento do informe #{$canceled['id']}.",
                'status'        => 5,
                'production_id' => $production->id,
            ]);
        });
    }
}
