<?php

namespace App\Services\Notes;

use App\Models\{HistoricNote, OvPhaseDuration};
use App\Support\SicodeRules;
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
        $byDate    = SicodeRules::ovPhasesOrderByDate();
        $now       = now();
        $rows      = [];
        $stale     = [];

        foreach ($noteIds as $noteId) {
            $list = ($events->get($noteId) ?? collect())->values();

            foreach (self::PHASES as $phase) {
                [$current, $next] = $this->phaseEdges($list, $phase, $byDate);

                if ($current === null || $next === null) {
                    $stale[] = [$noteId, $phase];

                    continue;
                }

                $start      = $current->dt_stats;
                $production = $producers[$noteId . '|' . $phase] ?? null;

                $rows[] = [
                    'note_id'       => $noteId,
                    'phase'         => $phase,
                    'started_at'    => $start,
                    'ended_at'      => $next->dt_stats,
                    'next_stat'     => (int) $next->num_stat,
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
                    ['started_at', 'ended_at', 'days', 'next_stat', 'company_id', 'production_id'],
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
     * Última passagem pelo status da fase e o evento que a encerra.
     *  - Padrão: a passagem de maior id_num; o término é o evento de menor id_num acima dela.
     *  - Por data (SP, numeração da origem fora de sequência): eventos ordenados por data (e id_num no empate);
     *    a passagem é a última nessa ordem e o término é o evento imediatamente acima.
     *
     * @param  \Illuminate\Support\Collection<int,HistoricNote>  $list
     * @return array{0:?HistoricNote,1:?HistoricNote}
     */
    private function phaseEdges($list, int $phase, bool $byDate): array
    {
        if ($byDate) {
            $ordered = $list
                ->sort(fn ($a, $b) => [$a->dt_stats->getTimestamp(), (int) $a->id_num] <=> [$b->dt_stats->getTimestamp(), (int) $b->id_num])
                ->values();

            $index = $ordered->keys()->filter(fn ($i) => (int) $ordered[$i]->num_stat === $phase)->last();

            return $index === null ? [null, null] : [$ordered[$index], $ordered->get($index + 1)];
        }

        $current = $list->filter(fn ($e) => (int) $e->num_stat === $phase)->sortBy('id_num')->last();
        $next    = $current === null ? null : $list->filter(fn ($e) => (int) $e->id_num > (int) $current->id_num)->sortBy('id_num')->first();

        return [$current, $next];
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
