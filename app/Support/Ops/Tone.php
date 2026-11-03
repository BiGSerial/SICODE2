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
}
