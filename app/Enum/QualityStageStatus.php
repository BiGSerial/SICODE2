<?php

namespace App\Enum;

enum QualityStageStatus: string
{
    case PENDING     = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case APPROVED    = 'APPROVED';
    case REJECTED    = 'REJECTED';
    case COMPLETED   = 'COMPLETED';
    case CANCELLED   = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING     => 'Aguardando',
            self::IN_PROGRESS => 'Em andamento',
            self::APPROVED    => 'Aprovada',
            self::REJECTED    => 'Rejeitada',
            self::COMPLETED   => 'Concluída',
            self::CANCELLED   => 'Cancelada',
        };
    }
}
