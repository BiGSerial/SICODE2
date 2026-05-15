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
}
