<?php

namespace App\Enum;

/** Ciclo do processo: PROJECT = 1º ciclo (ex.: Levantamento), BUDGET = 2º ciclo (ex.: Desenho). A atividade de cada ciclo é configurada. */
enum QualityStageType: string
{
    case PROJECT = 'PROJECT';
    case BUDGET  = 'BUDGET';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT => '1º ciclo',
            self::BUDGET  => '2º ciclo',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::PROJECT => '1º ciclo',
            self::BUDGET  => '2º ciclo',
        };
    }
}
