<?php

namespace App\Support\Ops;

use App\Services\Payment\BlockEvaluator;

/**
 * Situação da linha em telas operacionais (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §3).
 * A cor da linha TEM significado fixo e é a mesma em qualquer lista; a legenda (`<x-ops.legend>`) lê daqui.
 */
final class RowState
{
    public const FREE     = 'free';
    public const ASSIGNED = 'assigned';
    public const STACK    = 'stack';
    public const DONE     = 'done';
    public const LATE     = 'late';

    /** @return array<string, array{label: string, tone: string, hint: string}> */
    public static function all(): array
    {
        return [
            self::FREE     => ['label' => 'Livre', 'tone' => 'muted', 'hint' => 'Sem atividade aberta. Pode ser atribuída.'],
            self::ASSIGNED => ['label' => 'Com usuário', 'tone' => 'blue', 'hint' => 'Atribuída a um usuário e em andamento.'],
            self::STACK    => ['label' => 'Na pilha', 'tone' => 'amber', 'hint' => 'Está na pilha da empresa, ainda sem usuário.'],
            self::DONE     => ['label' => 'Concluída', 'tone' => 'green', 'hint' => 'Concluída há menos de 24 h; aguardando atualização do SAP.'],
            self::LATE     => ['label' => 'SAP pendente', 'tone' => 'red', 'hint' => 'Concluída há 24 h ou mais e o SAP não foi atualizado, ou informe rejeitado.'],
        ];
    }

    /** Converte o nível do `BlockEvaluator` na situação visual. */
    public static function fromBlock(int $block): string
    {
        return match ($block) {
            BlockEvaluator::HOLD_BLUE   => self::ASSIGNED,
            BlockEvaluator::HOLD_YELLOW => self::STACK,
            BlockEvaluator::HOLD_GREEN  => self::DONE,
            BlockEvaluator::HOLD_RED    => self::LATE,
            default                     => self::FREE,
        };
    }

    public static function tone(string $state): string
    {
        return self::all()[$state]['tone'] ?? 'muted';
    }

    public static function label(string $state): string
    {
        return self::all()[$state]['label'] ?? 'Livre';
    }
}
