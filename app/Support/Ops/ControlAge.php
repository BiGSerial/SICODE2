<?php

namespace App\Support\Ops;

use Carbon\CarbonInterface;

/**
 * Padrão das colunas "data + dias" das telas de CONTROLE (stack): quanto tempo faz que algo foi despachado, atribuído ou informado, com a
 * cor do prazo. É o que o componente `<x-ops.age>` desenha (o selo de `<x-ops.date>` com a data em cima e "N d" embaixo); a cor sai de
 * `Deadline::age` com as faixas abaixo, as mesmas que o Levantamento, a Fiscalização e o Comissionamento já aplicavam. Padrão do projeto:
 * docs/PADRAO_LISTAS_OPERACIONAIS.md §13.
 *
 * Regras (`rule`): `dispatch` (despachado há N dias: verde até 21, âmbar até 30, vermelho acima), `assigned` (atribuído há N dias: verde
 * até 7, âmbar até 9, vermelho acima) e `informed` (informado há N dias: verde até 5, âmbar até 8, vermelho acima), `ads` (ADS entregue há N dias: verde até 14, âmbar até 20, vermelho acima, a idade padrão do projeto) e `status` (status da nota no SAP, mesma faixa). Uma tela com faixa própria
 * passa `ok`/`warn` ao componente; faixa nova que valha para várias telas entra aqui, nunca em `@php` de view.
 */
final class ControlAge
{
    /** @var array<string, array{ok: int, warn: int, what: string}> */
    public const RULES = [
        'dispatch' => ['ok' => 21, 'warn' => 30, 'what' => 'do despacho'],
        'assigned' => ['ok' => 7, 'warn' => 9, 'what' => 'da atribuição ao responsável atual'],
        'informed' => ['ok' => 5, 'warn' => 8, 'what' => 'do informe'],
        'ads'      => ['ok' => 14, 'warn' => 20, 'what' => 'da ADS'],
        'status'   => ['ok' => 14, 'warn' => 20, 'what' => 'do status da nota no SAP'],
    ];

    /**
     * Faixa de uma regra, com sobrescrita opcional (`ok`/`warn`) e rótulo do que a data representa.
     *
     * @return array{ok: int, warn: int, what: string}
     */
    public static function rule(string $rule, ?int $ok = null, ?int $warn = null, ?string $what = null): array
    {
        // Sem regra nomeada vale a faixa de idade padrão do projeto (14/20, a mesma de Deadline::age).
        $base = self::RULES[$rule] ?? ['ok' => 14, 'warn' => 20, 'what' => 'da data'];

        return ['ok' => $ok ?? $base['ok'], 'warn' => $warn ?? $base['warn'], 'what' => $what ?? $base['what']];
    }

    /** Dias corridos desde a data (zero hoje; negativo se a data é futura); null sem data. */
    public static function days(?CarbonInterface $date): ?int
    {
        return $date ? (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay(), false) : null;
    }

    /**
     * Linha de baixo do selo de um prazo que VENCE (dias que faltam): "N d" enquanto não venceu, "N dias vencidos" depois (nunca "-N d").
     * Mesmo texto do prazo da medição no Pagamento.
     */
    public static function remainingLabel(?int $daysLeft): ?string
    {
        return match (true) {
            $daysLeft === null => null,
            $daysLeft < 0      => abs($daysLeft) . (abs($daysLeft) === 1 ? ' dia vencido' : ' dias vencidos'),
            default            => $daysLeft . ' d',
        };
    }

    /** @param array{ok: int, warn: int, what: string} $rule */
    public static function tone(?int $days, array $rule): string
    {
        return Deadline::age($days, $rule['ok'], $rule['warn']);
    }

    /**
     * Texto do tooltip: o que a data é, quanto tempo faz e a regra de cor, em português.
     *
     * @param array{ok: int, warn: int, what: string} $rule
     */
    public static function hint(?int $days, array $rule): string
    {
        $since = $days === null ? '' : ' ' . Deadline::ageText($days) . '.';

        return 'Data ' . $rule['what'] . ' e dias corridos desde então.' . $since . ' Verde até ' . $rule['ok'] . ' dias, âmbar até ' . $rule['warn'] . ', vermelho acima.';
    }
}
