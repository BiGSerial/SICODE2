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
        if ($note->txpriority === 'Emergente') {
            return false;
        }

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
            'local'       => 'PILHA AGUARDANDO PARA APROVAÇÃO',
            'register'    => null,
            'responsible' => null,
            'tacit'       => false,
        ];
    }
}
