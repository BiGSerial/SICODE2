<?php

namespace App\Services\D5;

use App\Models\FiveNote;
use App\Models\Production;
use Illuminate\Support\Collection;

/**
 * Resolve a D5 que uma produção (Fiscalização/Pagamento) deve tratar.
 *
 * Uma produção só trata D5 quando está marcada com `dfive` E associada a uma D5.
 * A associação é sempre explícita (pivot `productionables`, depois informe atual
 * da produção). Nunca se assume a "D5 da nota" quando há mais de uma candidata.
 */
class ProductionFiveNoteResolver
{
    public function resolve(Production $production): ?FiveNote
    {
        if (!$production->dfive) {
            return null;
        }

        $linked = $production->relationLoaded('fiveNotes')
            ? $production->fiveNotes
            : $production->fiveNotes()->get();

        if ($linked->isNotEmpty()) {
            return $this->pickLinked($production, $linked);
        }

        return null;
    }

    private function pickLinked(Production $production, Collection $linked): ?FiveNote
    {
        $active = $linked->where('is_archived', false);
        $pool   = $active->isNotEmpty() ? $active : $linked;

        if ($pool->count() === 1) {
            return $pool->first();
        }

        $reportIds = ($production->relationLoaded('WorkReportFlowProductions')
            ? $production->WorkReportFlowProductions
            : $production->WorkReportFlowProductions()->get())
            ->where('is_current', true)
            ->pluck('work_report_id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        $byReport = $pool->filter(fn (FiveNote $five) => $reportIds->contains((int) $five->work_report_id));

        return $byReport->count() === 1 ? $byReport->first() : null;
    }
}
