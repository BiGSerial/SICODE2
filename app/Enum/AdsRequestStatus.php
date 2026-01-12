<?php

namespace App\Enum;

enum AdsRequestStatus: string
{
    case QUEUED      = 'QUEUED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RETRY       = 'RETRY';
    case FAILED      = 'FAILED';
    case CANCELED    = 'CANCELED';
    case DONE        = 'DONE';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match($this) {
            self::QUEUED      => 'Na Fila',
            self::IN_PROGRESS => 'Em Progresso',
            self::RETRY       => 'Tentando Novamente',
            self::FAILED      => 'Falhou',
            self::CANCELED    => 'Cancelado',
            self::DONE        => 'Concluído',
        };
    }
}
