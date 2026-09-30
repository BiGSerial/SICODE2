<?php

namespace App\Enum;

enum QualitySapRequestStatus: string
{
    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case FAILED  = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::SUCCESS => 'Sucesso',
            self::FAILED  => 'Falha',
        };
    }
}
