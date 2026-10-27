<?php

namespace App\Services\Notes;

use App\Models\{HistoricNote, OvPhaseDuration};
use Illuminate\Support\Facades\DB;

/**
 * Deriva de historic_notes a duração das fases 27 (levantamento) e 28 (desenho).
 * Vale a última passagem pelo status; a fase termina no evento seguinte (id_num).
 * Fase ainda aberta não gera linha.
 */
class OvPhaseDurationService
{
    private const PHASES = [OvPhaseDuration::SURVEY, OvPhaseDuration::DESIGN];

    /**
     * @param  iterable<int>  $noteIds
     */
    public function rebuildForNotes(iterable $noteIds): void
    {
        $noteIds = collect($noteIds)->unique()->values();

        foreach ($noteIds->chunk(1000) as $ids) {
            $this->rebuildChunk($ids->all());
        }
    }

    private function rebuildChunk(array $noteIds): void
    {
        $events = HistoricNote::query()
            ->whereIn('note_id', $noteIds)
            ->orderBy('note_id')
            ->orderBy('id_num')
            ->get(['note_id', 'id_num', 'num_stat', 'dt_stats'])
            ->groupBy('note_id');

        $producers = $this->lastProductionByNotePhase($noteIds);
        $now       = now();
        $rows      = [];
        $stale     = [];

        foreach ($noteIds as $noteId) {
            $list = ($events->get($noteId) ?? collect())->values();

            foreach (self::PHASES as $phase) {
                $index = $list->keys()->filter(fn ($i) => (int) $list[$i]->num_stat === $phase)->last();
                $next  = $index === null ? null : $list->get($index + 1);

                if ($index === null || $next === null) {
                    $stale[] = [$noteId, $phase];

                    continue;
                }

                $start      = $list[$index]->dt_stats;
                $production = $producers[$noteId . '|' . $phase] ?? null;

                $rows[] = [
                    'note_id'       => $noteId,
                    'phase'         => $phase,
                    'started_at'    => $start,
                    'ended_at'      => $next->dt_stats,
                    'days'          => round(max(0, $start->diffInSeconds($next->dt_stats, false)) / 86400, 2),
                    'company_id'    => $production->company_id ?? null,
                    'production_id' => $production->id ?? null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        DB::transaction(function () use ($rows, $stale) {
            // updated_at fora da lista: fase sem mudança não é regravada.
            foreach (array_chunk($rows, 1000) as $chunk) {
                OvPhaseDuration::upsert(
                    $chunk,
                    ['note_id', 'phase'],
                    ['started_at', 'ended_at', 'days', 'company_id', 'production_id'],
                );
            }

            foreach (collect($stale)->groupBy(1) as $phase => $pairs) {
                OvPhaseDuration::where('phase', $phase)
                    ->whereIn('note_id', $pairs->pluck(0)->all())
                    ->delete();
            }
        });
    }

    /**
     * Última produção concluída do serviço de levantamento (27) e de projeto (28) por nota.
     *
     * @return array<string,object>
     */
    private function lastProductionByNotePhase(array $noteIds): array
    {
        $map = [];

        DB::table('productions as p')
            ->join('services as s', 's.uuid', '=', 'p.service_id')
            ->whereIn('p.note_id', $noteIds)
            ->whereIn('s.status', self::PHASES)
            ->where('p.completed', true)
            ->orderBy('p.completed_at')
            ->get(['p.id', 'p.note_id', 'p.company_id', 's.status'])
            ->each(function ($row) use (&$map) {
                $map[$row->note_id . '|' . $row->status] = $row;
            });

        return $map;
    }
}
