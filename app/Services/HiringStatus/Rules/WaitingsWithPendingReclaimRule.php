<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Regra para notas com Waitings cujo último waiting está pendente:
 * - Se reclaim.complete: CONTRATANTE
 * - Caso contrário: RI CONSTRUÇÃO / RI CIP
 */
class WaitingsWithPendingReclaimRule implements RuleInterface
{
    /**
     * Aplica-se quando existe ao menos um Waiting e o último não está complete.
     */
    public function supports(Note $note): bool
    {
        $waitings = $note->Waitings;
        if ($waitings->isEmpty()) {
            return false;
        }

        $lastWaiting = $waitings->last();
        return $lastWaiting->complete === false;
    }

    /**
     * Monta atributos para upsert quando há um waiting pendente.
     */
    public function handle(Note $note): array
    {
        $lastWaiting = $note->waitings->last();
        $reclaim    = $lastWaiting->reclaim;

        if ($reclaim->completed) {
            return [
                'last_date'   => $reclaim->completed_at,
                'position'    => 'CONTRATANTE',
                'local'       => 'RETORNO DO EM ESPERA PARA CONTRATAÇÃO',
                'register'    => $lastWaiting->user->Registration ?? null,
                'responsible' => $lastWaiting->user->name ?? null,
            ];
        }

        $service    = $reclaim->service;
        $production = $reclaim->production;
        $position   = $service && $service->construction ? 'RI CONSTRUÇÃO' : 'RI CIP';

        return [
            'last_date'   => $reclaim->created_at,
            'position'    => $position,
            'local'       => 'RETORNO DA CONTRATANTE EM ESPERA',
            'register'    => $production?->user?->Registration ?? null,
            'responsible' => $production?->user?->name ?? null,
        ];
    }
}
