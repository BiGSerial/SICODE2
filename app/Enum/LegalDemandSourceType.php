<?php

namespace App\Enum;

enum LegalDemandSourceType: string
{
    case INJUNCTION = 'injunction';
    case SENTENCE = 'sentence';
    case SUBSIDY = 'subsidy';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
