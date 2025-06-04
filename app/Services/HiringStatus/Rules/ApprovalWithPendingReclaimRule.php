<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;

/**
 * Regra para notas com Approval e Reclaim não completo:
 * posição 'RI CONSTRUÇÃO' ou 'RI CIP', conforme serviço.
 */
class ApprovalWithPendingReclaimRule implements RuleInterface
{
    /**
     * Aplica-se quando há approval não aprovado e existe ao menos um reclaim pendente.
     */
    public function supports(Note $note): bool
    {
        if (!$note->approval || $note->txpriority === 'Emergente') {
            return false;
        }

        $reclaims = $note->approval->reclaims;
        if ($reclaims->isEmpty()) {
            return false;
        }

        $lastReclaim = $reclaims->last();
        return $note->approval->approved === false && $lastReclaim->completed === false;
    }

    /**
     * Monta atributos para upsert quando o último reclaim está pendente.
     */
    public function handle(Note $note): array
    {
        $lastReclaim = $note->approval->reclaims->last();
        $service    = $lastReclaim->service;
        $production = $lastReclaim->production;

        $position = $service && $service->construction
            ? 'RI CONSTRUÇÃO'
            : 'RI CIP';

        return [
            'last_date'   => $lastReclaim->created_at,
            'position'    => $position,
            'local'       => 'RETORNADO PELA VERIFICAÇÃO DE PROJETOS',
            'register'    => $production?->user?->Registration ?? null,
            'responsible' => $production?->user?->name ?? null,
            'tacit'       => $note->approval->tacit,
        ];
    }
}
