<?php

namespace App\Support\Ops;

use Carbon\CarbonInterface;

/**
 * Regra única de cor para prazos em telas operacionais (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §4).
 * Devolve sempre um tom semântico (green|amber|red|muted); quem desenha é `<x-ops.date>`.
 */
final class Deadline
{
    /** Dias que faltam para vencer: vencido = vermelho; até `$warnUntil` dias = âmbar; acima = verde. */
    public static function remaining(?int $daysLeft, int $warnUntil = 15): string
    {
        return match (true) {
            $daysLeft === null      => 'muted',
            $daysLeft < 0           => 'red',
            $daysLeft <= $warnUntil => 'amber',
            default                 => 'green',
        };
    }

    /** Idade em dias de algo que "envelhece": até `$okUntil` verde; até `$warnUntil` âmbar; acima vermelho. */
    public static function age(?int $days, int $okUntil = 14, int $warnUntil = 20): string
    {
        return match (true) {
            $days === null     => 'muted',
            $days > $warnUntil => 'red',
            $days > $okUntil   => 'amber',
            default            => 'green',
        };
    }

    public static function daysFromNow(?CarbonInterface $date): ?int
    {
        return $date ? (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false) : null;
    }

    /** Texto curto para tooltip: "vence hoje", "vence em 3 dias", "venceu há 2 dias". */
    public static function remainingText(?int $daysLeft): string
    {
        return match (true) {
            $daysLeft === null => 'Sem prazo definido',
            $daysLeft === 0    => 'Vence hoje',
            $daysLeft > 0      => 'Vence em ' . $daysLeft . ($daysLeft === 1 ? ' dia' : ' dias'),
            default            => 'Venceu há ' . abs($daysLeft) . (abs($daysLeft) === 1 ? ' dia' : ' dias'),
        };
    }

    public static function ageText(?int $days): string
    {
        return match (true) {
            $days === null => 'Sem data',
            $days === 0    => 'Hoje',
            default        => 'Há ' . $days . ($days === 1 ? ' dia' : ' dias'),
        };
    }
}
