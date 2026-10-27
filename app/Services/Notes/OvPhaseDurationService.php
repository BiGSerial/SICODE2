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

    /** Status que expurga a fase: se o evento imediatamente acima for um destes (99), a fase não entra nos indicadores. */
    public const PURGE_STATUSES = [99];

    /** OVs expurgadas dos indicadores: número que começa com estes prefixos... */
    public const PURGE_NOTE_PREFIXES = ['800'];

    /** ...ou rubrica (notes.rubrica) nestes valores, como estão na base ("Incoporação" é a grafia gravada; a correta entra junto). */
    public const PURGE_RUBRICS = ['Acompanhamento', 'Incoporação', 'Incorporação', 'Desconsiderar'];

    /** Expurgada = a nota não gera fases e não aparece nos indicadores. */
    public static function isPurged(?string $note, ?string $rubric): bool
    {
        $note = trim((string) $note);

        foreach (self::PURGE_NOTE_PREFIXES as $prefix) {
            if ($note !== '' && str_starts_with($note, $prefix)) {
                return true;
            }
        }

        return in_array(mb_strtolower(trim((string) $rubric)), array_map('mb_strtolower', self::PURGE_RUBRICS), true);
    }

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

        $notes     = \App\Models\Note::query()->whereIn('id', $noteIds)->get(['id', 'note', 'rubrica'])->keyBy('id');
        $producers = $this->lastProductionByNotePhase($noteIds);
        $byDate    = SicodeRules::ovPhasesOrderByDate();
        $now       = now();
        $rows      = [];
        $stale     = [];

        foreach ($noteIds as $noteId) {
            $list = ($events->get($noteId) ?? collect())->values();

            // OV expurgada (prefixo 800 ou rubrica Acompanhamento/Incorporação): sem fases; apaga as que já existiam.
            if (self::isPurged($notes[$noteId]->note ?? null, $notes[$noteId]->rubrica ?? null)) {
                foreach (self::PHASES as $phase) {
                    $stale[] = [$noteId, $phase];
                }

                continue;
            }

            foreach (self::PHASES as $phase) {
                [$current, $next] = $this->phaseEdges($list, $phase, $byDate);

                if ($current === null || $next === null || in_array((int) $next->num_stat, self::PURGE_STATUSES, true)) {
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
