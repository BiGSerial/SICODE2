<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Regra para notas com Approval aprovado: ficam como CONTRATANTE.
 */
class ApprovalApprovedRule implements RuleInterface
{
    /**
     * Esta regra se aplica quando a nota possui Approval e approved == true.
     */
    public function supports(Note $note): bool
    {
        return $note->approval !== null && $note->approval->approved === true;
    }

    /**
     * Monta os atributos para upsert quando Approval aprovado.
     */
    public function handle(Note $note): array
    {
        return [
            'last_date' => $note->approval->approved_at,
            'position'  => 'CONTRATANTE',
            'tacit'     => $note->approval->tacit,
        ];
    }
}
