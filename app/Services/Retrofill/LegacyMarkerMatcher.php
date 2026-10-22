<?php

namespace App\Services\Retrofill;

use Carbon\CarbonInterface;

/**
 * Casa produções (ação humana, `dispatch_at` arbitrário) com marcos de referência (decisão da parcial,
 * conclusão da fiscalização, data do informe...).
 *
 * Regras:
 *  - o `dispatch_at` nunca pode ser anterior ao marco (é só um piso, não um critério de proximidade);
 *  - FIFO: marcos e produções em ordem cronológica, cada produção ocupa o marco livre mais antigo elegível;
 *  - marcos já consumidos por vínculos existentes ($fixed) não voltam ao pool;
 *  - sem marco livre elegível, a produção é uma reatribuição e segue o marco da produção anterior (só se $allowRepeat);
 *  - ambiguidade (menos produções que marcos livres e mais de um marco elegível) não gera vínculo.
 */
class LegacyMarkerMatcher
{
    public const BEFORE_MARKER = 'antes_do_marco';

    public const NO_MARKER = 'sem_marco';

    public const SURPLUS = 'excedente';

    public const AMBIGUOUS = 'ambigua';

    /**
     * @param  array<int|string, CarbonInterface>  $markers  chave => data do marco
     * @param  array<int, array{id: int, at: CarbonInterface}>  $productions  produções ainda sem vínculo
     * @param  array<int, array{id: int, at: CarbonInterface, marker: int|string}>  $fixed  vínculos já existentes
     * @return array{links: array<int, array{marker: int|string, reassigned: bool}>, rejected: array<int, string>}
     */
    public function match(array $markers, array $productions, array $fixed = [], bool $allowRepeat = true): array
    {
        $ordered = $markers;
        uasort($ordered, fn (CarbonInterface $a, CarbonInterface $b) => $a <=> $b);

        $consumed = [];
        $assigned = [];

        foreach ($fixed as $item) {
            $consumed[(string) $item['marker']] = true;
            $assigned[]                         = ['at' => $item['at'], 'marker' => $item['marker']];
        }

        usort($productions, fn (array $a, array $b) => [$a['at'], $a['id']] <=> [$b['at'], $b['id']]);

        $links    = [];
        $rejected = [];

        foreach ($productions as $production) {
            $free = array_filter(
                $ordered,
                fn (CarbonInterface $at, $key) => !isset($consumed[(string) $key]) && $at->lte($production['at']),
                ARRAY_FILTER_USE_BOTH
            );

            if ($free) {
                $freeAll = count(array_filter($ordered, fn ($at, $key) => !isset($consumed[(string) $key]), ARRAY_FILTER_USE_BOTH));

                if (count($free) > 1 && count($productions) < $freeAll) {
                    $rejected[$production['id']] = self::AMBIGUOUS;

                    continue;
                }

                $marker = array_key_first($free);

                $consumed[(string) $marker] = true;
                $assigned[]                 = ['at' => $production['at'], 'marker' => $marker];
                $links[$production['id']]   = ['marker' => $marker, 'reassigned' => false];

                continue;
            }

            if (!array_filter($ordered, fn (CarbonInterface $at) => $at->lte($production['at']))) {
                $rejected[$production['id']] = $ordered ? self::BEFORE_MARKER : self::NO_MARKER;

                continue;
            }

            $previous = $this->previous($assigned, $production['at']);

            if (!$allowRepeat || $previous === null) {
                $rejected[$production['id']] = self::SURPLUS;

                continue;
            }

            $assigned[]               = ['at' => $production['at'], 'marker' => $previous];
            $links[$production['id']] = ['marker' => $previous, 'reassigned' => true];
        }

        return ['links' => $links, 'rejected' => $rejected];
    }

    /** Marco da produção imediatamente anterior (por data) já atribuída. */
    private function previous(array $assigned, CarbonInterface $at): int|string|null
    {
        usort($assigned, fn (array $a, array $b) => $a['at'] <=> $b['at']);

        $found = null;

        foreach ($assigned as $item) {
            if ($item['at']->lte($at)) {
                $found = $item['marker'];
            }
        }

        return $found;
    }
}
