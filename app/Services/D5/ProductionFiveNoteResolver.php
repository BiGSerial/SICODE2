<?php

namespace App\Services\D5;

use App\Models\{FiveNote, Production};
use Illuminate\Support\Collection;

/**
 * Resolve a D5 que uma produção (Fiscalização/Pagamento) deve tratar.
 *
 * Uma produção só trata D5 quando está marcada com `dfive` E associada a uma D5.
 * A associação é sempre explícita (pivot `productionables`, depois a D5 do informe
 * atual da produção). Nunca se assume a "D5 da nota" quando há mais de uma candidata.
 */
class ProductionFiveNoteResolver
{
    public function resolve(Production $production): ?FiveNote
    {
        // Para as novas Productions de Medição, este é o vínculo autoritativo.
        // Pivots e links de fluxo abaixo permanecem apenas para o histórico.
        $contextFive = $production->relationLoaded('measurementContext')
            ? $production->measurementContext?->fiveNote
            : $production->measurementContext()->with('fiveNote')->first()?->fiveNote;

        if ($contextFive) {
            return $contextFive;
        }

        if (!$production->dfive) {
            return null;
        }

        $linked = $production->relationLoaded('fiveNotes')
            ? $production->fiveNotes
            : $production->fiveNotes()->get();

        if ($linked->isNotEmpty()) {
            return $this->pickLinked($production, $linked);
        }

        // Sem vínculo direto: vale a D5 do informe ao qual a produção está associada.
        $reportIds = $this->currentReportIds($production);

        if ($reportIds->isEmpty()) {
            return null;
        }

        $byReport = FiveNote::query()
            ->where('note_id', $production->note_id)
            ->whereIn('work_report_id', $reportIds)
            ->where('is_archived', false)
            ->limit(2)
            ->get();

        return $byReport->count() === 1 ? $byReport->first() : null;
    }

    private function currentReportIds(Production $production): Collection
    {
        return ($production->relationLoaded('WorkReportFlowProductions')
            ? $production->WorkReportFlowProductions
            : $production->WorkReportFlowProductions()->get())
            ->where('is_current', true)
            ->pluck('work_report_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function pickLinked(Production $production, Collection $linked): ?FiveNote
    {
        $active = $linked->where('is_archived', false);
        $pool   = $active->isNotEmpty() ? $active : $linked;

        if ($pool->count() === 1) {
            return $pool->first();
        }

        $reportIds = $this->currentReportIds($production);

        $byReport = $pool->filter(fn (FiveNote $five) => $reportIds->contains((int) $five->work_report_id));

        return $byReport->count() === 1 ? $byReport->first() : null;
    }
}
