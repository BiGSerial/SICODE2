<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;
use Illuminate\Support\Str;

/**
 * Se viability approved e not hired → CONTRATANTE
 */
class ViabilityApprovedHired implements RuleInterface
{
    public function supports(Note $note): bool
    {
        if ($note->viabilities->isEmpty()) {
            return false;
        }

        $v = $note->viabilities->last();
        return $v && $v->approved && $v->hired;
    }

    public function handle(Note $note): array
    {
        $v = $note->viabilities->last();

        // Agrega todas as operações filtradas em cada order
        $operations = $v->orders
            ->flatMap(fn ($order) => $order->operations);



        // Verifica se há operações e se todas são operacao 0010 com status CONF*
        $allConfirmed = $operations->isNotEmpty()
            && $operations->every(
                fn ($op) =>
                $op->operacao === '0010' && Str::startsWith($op->status, 'CONF')
            );

        // Se não estiverem todas confirmadas, reverte hired
        if (!$allConfirmed) {
            return [
                'last_date'   => $v->hired_at,
                'position'    => 'CONTRATANTE',
                'local'       => 'INCONSISTENCIA DE CONTRATAÇÃO NO SAP',
                'register'    => $v->user?->Registration ?? null,
                'responsible' => $v->user?->name ?? null,
            ];
        }

        return [
            'last_date'   => $v->hired_at,
            'position'    => 'CONTRATADO',
            'local'       => 'CONTRATAÇÃO FINALIZADA',
            'register'    => $v->user?->Registration ?? null,
            'responsible' => $v->user?->name ?? null,
        ];
    }
}
