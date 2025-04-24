<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Se viability rejeitada e último reclaim completado → PROGRAMADOR
 */
class ViabilityRejectedWithCompletedReclaimRule implements RuleInterface
{
    public function supports(Note $note): bool
    {
        if ($note->viabilities->isEmpty()) {
            return false;
        }
        
        $v = $note->viabilities->last();
        if (!$v || !$v->rejected) {
            return false;
        }
        $r = $v->reclaims->last();
        return $r && $r->completed;
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();
        $r = $v->reclaims->last();

        return [
            'last_date'   => $r->completed_at,
            'position'    => 'PROGRAMADOR',
            'local'       => 'RETORNO DO RI VIABILIDADE',
            'register'    => $v->engineer?->Registration ?? null,
            'responsible' => $v->engineer?->name ?? null,
        ];
    }
}
