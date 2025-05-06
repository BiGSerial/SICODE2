<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Se viability rejeitada e último reclaim pendente → RI CONSTRUÇÃO / RI CIP
 */
class ViabilityRejectedWithPendingReclaimRule implements RuleInterface
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
        return $r && !$r->completed;
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();
        $r = $v->reclaims->last();
        $position = $r->service->construction ? 'RI CONSTRUÇÃO' : 'RI CIP';

        return [
            'last_date'   => $r->created_at,
            'position'    => $position,
            'local'       => 'RETORNO DA VIABILIDADE',
            'register'    => $r->production?->user?->Registration ?? null,
            'responsible' => $r->production?->user?->name ?? null,
        ];
    }
}
