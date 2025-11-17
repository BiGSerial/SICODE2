<?php

namespace App\Enum;

enum ProtestType: int
{
    case BTZERO         = 1;
    case CONSTRUCTION   = 2;
    case CIP            = 3;


    public function label(): string
    {
        return match ($this) {
            self::BTZERO         => 'BT Zero',
            self::CONSTRUCTION   => 'Construção',
            self::CIP            => 'CIP',
        };
    }

    public function badgeClass(): string
    {

        return match ($this) {
            self::BTZERO         => 'badge bg-secondary',
            self::CONSTRUCTION   => 'badge bg-primary',
            self::CIP            => 'badge bg-warning text-dark',
        };
    }

}
