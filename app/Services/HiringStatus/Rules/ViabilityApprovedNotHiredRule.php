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
        $v = $note->viabilities->last();
        return $v && $v->approved && !$v->hired;
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();

        return [
            'last_date'   => $v->completed_at,
            'position'    => 'CONTRATANTE',
            'register'    => $v->user?->Registration ?? null,
            'responsible' => $v->user?->name ?? null,
        ];
    }
}
