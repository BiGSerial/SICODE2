<?php

namespace App\Enum;

enum ProtestJobPriority: string
{
    case LOW    = 'low';
    case NORMAL = 'normal';
    case HIGH   = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW    => 'Baixa',
            self::NORMAL => 'Normal',
            self::HIGH   => 'Alta',
            self::URGENT => 'Urgente',
        };
    }

    public function badgeClass(): string
    {
        // classes bootstrap-ish só de exemplo, pode adaptar ao seu tema
        return match ($this) {
            self::LOW    => 'badge bg-secondary',
            self::NORMAL => 'badge bg-primary',
            self::HIGH   => 'badge bg-warning text-dark',
            self::URGENT => 'badge bg-danger',
        };
    }

    public function sortWeight(): int
    {
        // útil pra orderBy('priority') sem gambiarra:
        // quanto maior, mais crítico
        return match ($this) {
            self::LOW    => 1,
            self::NORMAL => 2,
            self::HIGH   => 3,
            self::URGENT => 4,
        };
    }
}
