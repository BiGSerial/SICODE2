<?php

namespace App\Services\Closure;

/**
 * Busca em massa de Notas/Ordens: o usuário cola uma lista (uma por linha, ou separada por vírgula, ponto e vírgula ou espaço) e a
 * lista filtra por correspondência EXATA em `orders.ordem` ou `notes.note`. Aqui só a interpretação do texto e o resumo
 * "encontradas × não encontradas"; o filtro em si é aplicado por cada lista (Despacho: ClosureDispatchListQuery; demais: ClosureWorkListing).
 */
final class ClosureBulkSearch
{
    /** Limite de termos por busca (protege a consulta e a tela). */
    public const MAX_TERMS = 2000;

    /**
     * Termos distintos, na ordem em que foram colados.
     *
     * @return array<int, string>
     */
    public static function parse(?string $text): array
    {
        $tokens = preg_split('/[\s,;]+/u', (string) $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms  = [];

        foreach ($tokens as $token) {
            $token = trim($token, " \t\n\r\0\x0B\"'`");

            if ($token !== '' && !isset($terms[$token])) {
                $terms[$token] = $token;
            }
        }

        return array_slice(array_values($terms), 0, self::MAX_TERMS);
    }

    /**
     * Resumo da busca: quantos termos foram encontrados e quais não casaram com nenhuma Ordem/Nota.
     *
     * @param array<int, string>   $terms
     * @param iterable<int, mixed> $foundValues Ordens e Notas (texto) das linhas que a lista devolveu
     *
     * @return array{total: int, found: int, missing: array<int, string>}
     */
    public static function summary(array $terms, iterable $foundValues): array
    {
        $present = [];

        foreach ($foundValues as $value) {
            $present[trim((string) $value)] = true;
        }

        $missing = array_values(array_filter($terms, fn (string $term) => !isset($present[$term])));

        return ['total' => count($terms), 'found' => count($terms) - count($missing), 'missing' => $missing];
    }
}
