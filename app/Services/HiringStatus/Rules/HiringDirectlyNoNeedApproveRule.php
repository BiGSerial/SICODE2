<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Se viability approved e not hired → CONTRATANTE
 */
class HiringDirectlyNoNeedApproveRule implements RuleInterface
{
    public function supports(Note $note): bool
    {

        if (!$note->viabilities->isEmpty()) {
            return false;
        }

        return $note->txpriority === 'Emergente';
    }

    public function handle(Note $note): array
    {
        return [
           'last_date'   => $note->dt_status,
           'position'    => 'CONTRATANTE',
           'local'       => 'CONTRATAÇÃO SEM NECESSIDADE DE APROVAÇÃO DE PROJETO',
           'register'    => null,
           'responsible' => null,
           'tacit'       => false,
        ];
    }
}
