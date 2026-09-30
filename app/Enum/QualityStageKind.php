<?php

namespace App\Enum;

/** Natureza de cada linha de `quality_stages`. */
enum QualityStageKind: string
{
    case DISPATCH  = 'DISPATCH';   // N1 precisa despachar ao usuário (início de ciclo)
    case RETURN    = 'RETURN';     // N2 devolveu ao N1, que precisa encaminhar ao usuário
    case EXECUTION = 'EXECUTION';  // atividade do usuário (Desenho)
    case REVIEW    = 'REVIEW';     // análise N1 ou N2

    public function label(): string
    {
        return match ($this) {
            self::DISPATCH  => 'Despacho ao usuário',
            self::RETURN    => 'Devolução do N2',
            self::EXECUTION => 'Execução',
            self::REVIEW    => 'Análise',
        };
    }
}
