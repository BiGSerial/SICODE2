<?php

namespace App\Services\Ads;

use Carbon\Carbon;

class TacitFineCalculator
{
    public function __construct(private ?AdsDeadlinePolicy $deadlinePolicy = null)
    {
        $this->deadlinePolicy ??= app(AdsDeadlinePolicy::class);
    }

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
        float $taxaMaxima = 0.10
    ): array
    {
        $base = max(0, $valorBase);
        $diasMulta = max(0, $dias);

        $taxaTotal = match (true) {
            $diasMulta <= 0 => 0.0,
            $diasMulta <= 10 => $diasMulta * $taxaDiaria,
            default => $taxaMaxima,
        };
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
        return $this->deadlinePolicy->lateDays($dataVencimentoTacito, $dataEnvioTacita, $referenciaAberto);
    }
}
