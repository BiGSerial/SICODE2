<?php

namespace App\Enum;

enum QualityStageLevel: string
{
    case DRAWING = 'DRAWING';
    case N1      = 'N1';
    case N2      = 'N2';

    public function label(): string
    {
        return match ($this) {
            self::DRAWING => 'Usuário',
            self::N1      => 'N1',
            self::N2      => 'N2',
        };
    }
}
