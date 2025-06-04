<?php

namespace App\Services\HiringStatus\Rules;

use App\Models\Note;
use App\Services\HiringStatus\RuleInterface;
use Illuminate\Support\Str;

/**
 * Se todos os orders têm statusSist começando com ENTE, ENCE ou CANC:
 *  - se o prefixo predominante for ENTE → ENCERRADA TECNICAMENTE
 */
class FinishedOrNotNeedHiring implements RuleInterface
{
    public function supports(Note $note): bool
    {
        // (A) todos os orders têm prefixo válido?
        if ($note->orders->isNotEmpty() &&
        $note->orders->every(fn ($o) => Str::startsWith($o->statusSist, ['ENTE','ENCE','CANC']))
        ) {
            return true;
        }

        // (B) sem viabilities e fora do escopo original?
        if ($note->viabilities->isEmpty() && ! $this->matchesOriginalFilter($note) &&
            ($note->waitings()->doesntExist() || $note->waitings?->last()->complete)
        ) {
            return true;
        }

        return false;
    }

    public function handle(Note $note): array
    {
        // 1) Caso (A): todos os orders validos
        if ($note->orders->isNotEmpty() &&
        $note->orders->every(fn ($o) => Str::startsWith($o->statusSist, ['ENTE','ENCE','CANC']))
        ) {
            // Conta prefixos
            $counts = $note->orders
                ->map(
                    fn ($o) =>
                    Str::startsWith($o->statusSist, 'ENTE') ? 'ENTE'
             : (Str::startsWith($o->statusSist, 'ENCE') ? 'ENCE' : 'CANC')
                )
                ->countBy();

            $pred = $counts->sort()->keys()->last();

            return match($pred) {
                'ENTE' => [
                    'last_date'   => $note->dt_status,
                    'position'    => 'ENCERRADA TECNICAMENTE',
                    'local'       => null,
                    'register'    => null,
                    'responsible' => null,
                ],
                'ENCE' => [
                    'last_date'   => $note->dt_status,
                    'position'    => 'ENCERRADA',
                    'local'       => null,
                    'register'    => null,
                    'responsible' => null,
                ],
                'CANC' => [
                    'last_date'   => $note->dt_status,
                    'position'    => 'CANCELADA',
                    'local'       => null,
                    'register'    => null,
                    'responsible' => null,
                ],
            };
        }


        // 2) Caso especial: Obra contratada sem viabilidade
        $operation = $note->orders
            ->filter(fn ($order) => Str::startsWith($order->statusSist, ['ABER', 'LIB']))
            ->flatMap(fn ($order) => $order->operations)
            ->first(
                fn ($op) =>
                $op->operacao === '0010' && Str::startsWith($op->status, 'CONF')
            );

        if ($operation && $note->viabilities->isEmpty()) {
            return [
                'last_date'   => $operation->fimReal ?? $note->dt_status, // fallback se fimReal for null
                'position'    => 'INCONSISTÊNCIA',
                'local'       => 'OBRA CONTRATADA SEM REGISTRO PARA VIABILIDADE NO SICODE',
                'register'    => null,
                'responsible' => null,
            ];
        }

        // 3) Caso (B): inconsistência
        return [
            'last_date'   => $note->dt_status,
            'position'    => 'INCONSISTÊNCIA',
            'local'       => 'OBRA APRESENTA INCONSISTÊNCIA DE STATUS',
            'register'    => null,
            'responsible' => null,
        ];
    }

    private function matchesOriginalFilter(Note $note): bool
    {
        // Tipo 2 + nstats em 46..50
        $cond1 = $note->type_note === 2
               && in_array((int)$note->nstats, [46,47,48,49,50], true);

        // Tipo 1 + centerjob LIKE 'VIAB%' ou null
        $cond2 = $note->type_note === 1
               && (
                   is_null($note->centerjob)
                || Str::startsWith($note->centerjob, 'VIAB')
               );

        return $cond1 || $cond2;
    }
}
