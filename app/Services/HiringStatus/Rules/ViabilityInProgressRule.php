<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Se viability em progresso (status 1 ou not completed/rejected/approved) → EMPREITEIRA
 */
class ViabilityInProgressRule implements RuleInterface
{
    public function supports(Note $note): bool
    {
        if ($note->viabilities->isEmpty()) {
            return false;
        }
        
        $v = $note->viabilities->last();
        return $v && ((!$v->rejected && !$v->approved) || $v->status === 1);
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();

        return [
            'last_date'   => $v->sended_at,
            'position'    => 'EMPREITEIRA',
            'local'       => 'EM VIABILIDADE',
            'register'    => null,
            'responsible' => $v->company?->name ?? null,
        ];
    }
}
