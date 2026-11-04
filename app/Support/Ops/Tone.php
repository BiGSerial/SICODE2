<?php

namespace App\Support\Ops;

/** Tradução de cores legadas (Bootstrap) para o tom semântico das listas operacionais (`green|blue|amber|red|teal|muted`). */
final class Tone
{
    public static function fromBootstrap(?string $color): string
    {
        return match ($color) {
            'success' => 'green',
            'primary' => 'blue',
            'warning' => 'amber',
            'danger'  => 'red',
            'info'    => 'teal',
            default   => 'muted',
        };
    }

    /** Tom do status de uma Production (`App\Custom\Notestatus`). */
    public static function forProductionStatus(int|string|null $status): string
    {
        return self::fromBootstrap(\App\Custom\Notestatus::status($status)->color ?? null);
    }

    /** Tom da situação operacional de uma Ordem do Encerramento (`App\Enum\ClosureOperationalStatus`). */
    public static function forClosureStatus(?string $status): string
    {
        return match ($status) {
            'ASSIGNED'    => 'blue',
            'IN_PROGRESS' => 'blue',
            'WAITING_PARTNER', 'WAITING' => 'amber',
            'DEPOSIT_BLOCKED' => 'red',
            'DEPOSIT_RELEASED', 'FINISHED', 'CLOSED' => 'green',
            default => 'muted',
        };
    }

    /** Tom do tipo de Ordem do Encerramento: P passivo (vermelho), M meta atual (verde), F meta futura (azul). */
    public static function forClosureType(?string $letter): string
    {
        return match ($letter) {
            'P'     => 'red',
            'M'     => 'green',
            'F'     => 'blue',
            default => 'muted',
        };
    }
}
