<?php

namespace App\Services\D5;

use App\Models\{FiveNote, WorkReport};

/**
 * Recupera a associação de uma D5 legada não passiva com o Informe Final da nota.
 *
 * A regra é causal: uma D5 pertence primeiro ao Informe mais recente que já existia
 * quando ela foi criada. Só na ausência de um Informe anterior usamos o primeiro
 * Informe criado depois dela. Isso impede que um Informe futuro "puxe" uma D5 antiga.
 */
class D5WorkReportAssociationResolver
{
    public function closestFor(FiveNote $fiveNote): ?WorkReport
    {
        if (!$fiveNote->note_id || $fiveNote->isPassive) {
            return null;
        }

        $reference = $fiveNote->created_at ?: now();

        $reports = WorkReport::query()
            ->where('note_id', $fiveNote->note_id)
            ->where('canceled', false)
            ->whereNotNull('created_at');

        // Informe que já existia no instante da D5: escolhe o mais recente.
        $previous = (clone $reports)
            ->whereRaw('COALESCE(informed_at, created_at) <= ?', [$reference])
            ->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderBy('id')
            ->first();

        if ($previous) {
            return $previous;
        }

        // Exceção para bases legadas em que a data da D5 é anterior à importação
        // dos Informes: usa o primeiro Informe posterior, de forma determinística.
        return $reports
            ->whereRaw('COALESCE(informed_at, created_at) > ?', [$reference])
            ->orderByRaw('COALESCE(informed_at, created_at) ASC')
            ->orderBy('id')
            ->first();
    }
}
