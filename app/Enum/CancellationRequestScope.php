<?php

namespace App\Enum;

enum CancellationRequestScope: string
{
    case NOTE_FULL = 'NOTE_FULL';
    case ORDERS_PARTIAL = 'ORDERS_PARTIAL';

    public function label(): string
    {
        return match ($this) {
            self::NOTE_FULL => 'Nota inteira',
            self::ORDERS_PARTIAL => 'Ordens específicas',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NOTE_FULL => 'bg-primary',
            self::ORDERS_PARTIAL => 'bg-warning text-dark',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $item) => $item->value, self::cases());
    }
}
