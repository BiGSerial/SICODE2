<?php

namespace App\Services\Ads;

use Carbon\Carbon;

class TacitFineCalculator
{
    /**
     * @return array{
     *   valor_diario: float,
     *   valor_total: float,
     *   percentual_aplicado: float
     * }
     */
    public function calcularMultaPrevistaLinear(
        float $valorBase,
        int $dias,
        float $taxaDiaria = 0.005,
        float $taxaMaxima = 0.15
    ): array
    {
        $base = max(0, $valorBase);
        $diasMulta = max(0, $dias);

        // Regra de negócio:
        // - até 10 dias: juros simples de 0,5% ao dia
        // - a partir do 11º dia: percentual fixo de 15%
        $taxaTotal = $diasMulta >= 11
            ? $taxaMaxima
            : ($diasMulta * $taxaDiaria);
        $valorDiario = $base * $taxaDiaria;
        $valorTotal = $base * $taxaTotal;

        return [
            'valor_diario' => round($valorDiario, 2),
            'valor_total' => round($valorTotal, 2),
            'percentual_aplicado' => round($taxaTotal * 100, 2),
        ];
    }

    public function calcularDiasMulta(?Carbon $dataVencimentoTacito, ?Carbon $dataEnvioTacita, ?Carbon $referenciaAberto = null): int
    {
        if (!$dataVencimentoTacito) {
            return 0;
        }

        // Regra inclusiva: o dia do vencimento tácito já conta como 1 dia de multa.
        $inicio = $dataVencimentoTacito->copy()->startOfDay();
        $fim = ($dataEnvioTacita ?: ($referenciaAberto ?: now()))->copy()->startOfDay();

        if ($fim->lt($inicio)) {
            return 0;
        }

        return $inicio->diffInDays($fim) + 1;
    }
}
