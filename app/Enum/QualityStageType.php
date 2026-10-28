<?php

namespace App\Enum;

/** Ciclo do processo: PROJECT = 1º ciclo (ex.: Levantamento), BUDGET = 2º ciclo (ex.: Desenho). A atividade de cada ciclo é configurada. */
enum QualityStageType: string
{
    case PROJECT = 'PROJECT';
    case BUDGET  = 'BUDGET';

    /** Nome exibido ao usuário, fixo no código (não vem do serviço configurado para o ciclo). */
    public function label(): string
    {
        return match ($this) {
            self::PROJECT => '1º Ciclo – Levantamento/Desenho',
            self::BUDGET  => '2º Ciclo – Orçamento',
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
