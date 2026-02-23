<?php

namespace App\Services\Ads;

use Carbon\Carbon;

class TacitFineCalculator
{
    /**
     * @return array{valor_diario: float, valor_total: float}
     */
    public function calcularMultaPrevistaLinear(float $valorBase, int $dias, float $taxaDiaria = 0.05): array
    {
        $valorDiario = max(0, $valorBase) * $taxaDiaria;
        $valorTotal = $valorDiario * max(0, $dias);

        return [
            'valor_diario' => round($valorDiario, 2),
            'valor_total' => round($valorTotal, 2),
        ];
    }

    public function calcularDiasMulta(?Carbon $dataVencimentoTacito, ?Carbon $dataEnvioTacita, ?Carbon $referenciaAberto = null): int
    {
        if (!$dataVencimentoTacito) {
            return 0;
        }

        $fim = $dataEnvioTacita ?: ($referenciaAberto ?: now());
        $dias = $dataVencimentoTacito->diffInDays($fim, false);

        return max(0, $dias);
    }
}
