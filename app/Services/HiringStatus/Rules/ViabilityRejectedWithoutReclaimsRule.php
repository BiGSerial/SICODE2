<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Se viability rejeitada e sem reclaims → PROGRAMADOR
 */
class ViabilityRejectedWithoutReclaimsRule implements RuleInterface
{
    public function supports(Note $note): bool
    {
        $v = $note->viabilities->last();
        return $v && $v->rejected && ($v->reclaims->isEmpty() || $v->status == 6);
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();

        return [
            'last_date'   => $v->returned_at,
            'position'    => 'PROGRAMADOR',
            'register'    => $v->engineer?->Registration ?? null,
            'responsible' => $v->engineer?->name ?? null,
        ];
    }
}
