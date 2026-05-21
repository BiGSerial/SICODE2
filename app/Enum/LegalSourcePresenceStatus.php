<?php

namespace App\Enum;

enum LegalSourcePresenceStatus: string
{
    case PRESENT = 'present';
    case MISSING = 'missing';
    case RETURNED = 'returned';
    case IGNORED = 'ignored';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Presente',
            self::MISSING => 'Ausente',
            self::RETURNED => 'Retornado',
            self::IGNORED => 'Ignorado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PRESENT => 'badge bg-success',
            self::MISSING => 'badge bg-danger',
            self::RETURNED => 'badge bg-warning text-dark',
            self::IGNORED => 'badge bg-secondary',
        };
    }
}
