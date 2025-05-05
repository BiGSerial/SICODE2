<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Se viability approved e not hired → CONTRATANTE
 */
class ViabilityApprovedNotHiredRule implements RuleInterface
{
    public function supports(Note $note): bool
    {

        if ($note->viabilities->isEmpty()) {
            return false;
        }

        $v = $note->viabilities->last();
        return $v && $v->approved && !$v->hired;
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();

        if ($v->tacit) {
            $date = $v->tacit_at;
        } else {
            $date = $v->returned_at;
        }

        return [
            'last_date'   => $date,
            'position'    => 'CONTRATANTE',
            'local'       => 'CONTRATAÇÃO PÓS VIABILIDADE',
            'register'    => $v->User?->Registration ?? null,
            'responsible' => $v->User?->name ?? null,
        ];
    }
}
