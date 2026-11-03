<?php

namespace App\Support\Ops;

use Carbon\CarbonInterface;

/**
 * "Prazo real" de uma Nota/OV nas listas operacionais de Serviços: 30 dias de execução, contados de duas formas conforme a atividade.
 *  - `created`: dias corridos desde a criação da nota (Análises, Pré-análise);
 *  - `days_left`: dias que faltam segundo o SAP, que já descontam os tempos em status não contabilizáveis (Cadastro, Incorporação, Reverso).
 * Em tela o prazo real é a DATA DE VENCIMENTO da nota, com os dias que faltam embaixo (`<x-ops.prazo>`): vencido ou até 5 dias vermelho,
 * 6 a 9 âmbar, 10 ou mais verde. Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 */
final class PrazoReal
{
    public const EXECUTION_DAYS = 30;

    public const SOURCE_CREATED = 'created';

    public const SOURCE_SAP = 'days_left';

    /** Dias que faltam para vencer (negativo = vencido); null sem informação. */
    public static function daysLeft(?CarbonInterface $createdAt, mixed $sapDaysLeft, string $source, ?CarbonInterface $now = null): ?int
    {
        if ($source === self::SOURCE_SAP) {
            return $sapDaysLeft === null || $sapDaysLeft === '' ? null : (int) $sapDaysLeft;
        }

        if (!$createdAt) {
            return null;
        }

        $now ??= now();

        return self::EXECUTION_DAYS - (int) $createdAt->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());
    }

    /** Data de vencimento: hoje + os dias que faltam (para o prazo contado da criação, é a criação + 30 dias). */
    public static function dueDate(?int $daysLeft, ?CarbonInterface $now = null): ?CarbonInterface
    {
        return $daysLeft === null ? null : ($now ?? now())->copy()->startOfDay()->addDays($daysLeft);
    }

    /** Dias que já passaram desde o início do prazo. */
    public static function elapsed(?int $daysLeft): ?int
    {
        return $daysLeft === null ? null : self::EXECUTION_DAYS - $daysLeft;
    }

    public static function tone(?int $daysLeft): string
    {
        return match (true) {
            $daysLeft === null => 'muted',
            $daysLeft < 6      => 'red',   // vencido (negativo) e até 5 dias
            $daysLeft < 10     => 'amber',
            default            => 'green',
        };
    }

    public static function hint(?int $daysLeft, string $source): string
    {
        $what = $source === self::SOURCE_SAP
            ? 'Dias do prazo real, já sem os tempos em status não contabilizáveis. '
            : 'Dias corridos desde a criação da Nota/OV. ';

        return $what . match (true) {
            $daysLeft === null => 'Sem prazo informado.',
            $daysLeft < 0      => 'Prazo vencido.',
            default            => $daysLeft . ($daysLeft === 1 ? ' dia' : ' dias') . ' para vencer',
        } . ' (vermelho vencido ou até 5 dias, âmbar de 6 a 9, verde a partir de 10).';
    }
}
