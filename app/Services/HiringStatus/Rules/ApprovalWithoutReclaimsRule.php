<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Regra para notas com Approval mas sem Reclaims: ficam como PROGRAMADOR.
 */
class ApprovalWithoutReclaimsRule implements RuleInterface
{
    /**
     * Aplica-se quando existe Approval e não há Reclaims.
     */
    public function supports(Note $note): bool
    {
        if (!$note->approval || $note->txpriority === 'Emergente') {
            return false;
        }

        return $note->approval !== null
            && $note->approval->approved === false
            && $note->approval->reclaims->isEmpty();
    }

    /**
     * Monta atributos para upsert quando Approval sem Reclaims.
     */
    public function handle(Note $note): array
    {
        return [
            'last_date'   => $note->approval->created_at,
            'position'    => 'PROGRAMADOR',
            'local'       => 'EM ANALISE PELO PROGRAMADOR',
            'register'    => $note->approval->user->Registration ?? null,
            'responsible' => $note->approval->user->name ?? null,
            'tacit'       => $note->approval->tacit,
        ];
    }
}
