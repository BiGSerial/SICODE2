<?php

namespace App\Support\Ops;

/**
 * Cor do selo de prazo nos quadros "Prazos" do Portal da parceira: vencido vermelho, de 3 dias antes até o dia do vencimento amarelo
 * (âmbar), demais situações verde.
 */
final class PrazoParceira
{
    public const WARNING_DAYS = 3;

    public static function tone(?int $daysLeft): string
    {
        return match (true) {
            $daysLeft === null              => 'muted',
            $daysLeft < 0                   => 'red',
            $daysLeft <= self::WARNING_DAYS => 'amber',
            default                         => 'green',
        };
    }
}
