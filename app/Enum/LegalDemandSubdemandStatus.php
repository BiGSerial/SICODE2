<?php

namespace App\Enum;

enum LegalDemandSubdemandStatus: string
{
    case ABERTA = 'aberta';
    case EM_ANDAMENTO = 'em_andamento';
    case AGUARDANDO_RETORNO = 'aguardando_retorno';
    case CONCLUIDA = 'concluida';
    case ENCERRADA_CONTROLADOR = 'encerrada_controlador';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ABERTA => 'Aberta',
            self::EM_ANDAMENTO => 'Em andamento',
            self::AGUARDANDO_RETORNO => 'Aguardando retorno',
            self::CONCLUIDA => 'Concluída',
            self::ENCERRADA_CONTROLADOR => 'Encerrada pelo controlador',
        };
    }
}
