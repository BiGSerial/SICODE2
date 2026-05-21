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

    public function label(): string
    {
        return match ($this) {
            self::INJUNCTION => 'Liminar',
            self::SENTENCE => 'Sentença',
            self::SUBSIDY => 'Subsídio',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::INJUNCTION => 'badge bg-danger',
            self::SENTENCE => 'badge bg-warning text-dark',
            self::SUBSIDY => 'badge bg-info text-dark',
        };
    }
}
