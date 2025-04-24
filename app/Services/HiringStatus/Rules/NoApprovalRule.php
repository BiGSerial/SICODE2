<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

class NoApprovalRule implements RuleInterface
{
    /**
    * Esta regra se aplica quando a nota não possui um Approval.
    */
    public function supports(Note $note): bool
    {
        return $note->approval === null;
    }

    /**
     * Monta os atributos para upsert de notas sem Approval.
     */
    public function handle(Note $note): array
    {
        return [
            'last_date'   => $note->dt_status,
            'position'    => 'PILHA PROGRAMADORES',
            'register'    => null,
            'responsible' => null,
            'tacit'       => false,
        ];
    }
}
