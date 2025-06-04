<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Regra para notas com Approval e Reclaim completado:
 * posição PROGRAMADOR, retorna para programador.
 */
class ApprovalWithCompletedReclaimRule implements RuleInterface
{
    /**
     * Aplica-se quando existe approval não aprovado e o último reclaim foi completado.
     */
    public function supports(Note $note): bool
    {
        if (!$note->approval || $note->txpriority === 'Emergente') {
            return false;
        }

        $reclaims = $note->approval->reclaims;
        if ($reclaims->isEmpty()) {
            return false;
        }

        $lastReclaim = $reclaims->last();
        return $note->approval->approved === false && $lastReclaim->completed === true;
    }

    /**
     * Monta atributos para upsert quando o reclaim foi completado.
     */
    public function handle(Note $note): array
    {
        $lastReclaim = $note->approval->reclaims->last();

        return [
            'last_date'   => $lastReclaim->completed_at,
            'position'    => 'PROGRAMADOR',
            'local'       => 'RETORNADO RI ANALISE',
            'register'    => $note->approval->user?->Registration ?? null,
            'responsible' => $note->approval->user?->name ?? null,
            'tacit'       => $note->approval->tacit,
        ];
    }
}
