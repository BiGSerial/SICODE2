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
        if ($note->viabilities->isEmpty()) {
            return false;
        }

        $v = $note->viabilities->last();
        return $v && $v->rejected && ($v->reclaims->isEmpty() || $v->status === 4);
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();

        return [
            'last_date'   => $v->returned_at,
            'position'    => 'PROGRAMADOR',
            'local'       => 'RESPOSTA VIABILIDADE',
            'register'    => $v->Engineer?->Registration ?? null,
            'responsible' => $v->Engineer?->name ?? null,
        ];
    }
}
