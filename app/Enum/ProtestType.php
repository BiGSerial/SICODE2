<?php

namespace App\Enum;

enum ProtestType: int
{
    case CIP            = 1;
    case CONSTRUCTION   = 2;
    case BTZERO         = 3;




    public function label(): string
    {
        return match ($this) {
            self::BTZERO         => 'BT Zero',
            self::CONSTRUCTION   => 'Construção',
            self::CIP            => 'CIP',
            default              => 'Desconhecido',
        };
    }

    public function badgeClass(): string
    {

        return match ($this) {
            self::BTZERO         => 'badge bg-secondary',
            self::CONSTRUCTION   => 'badge bg-primary',
            self::CIP            => 'badge bg-warning text-dark',
            default              => 'badge bg-dark',
        };
    }

}
