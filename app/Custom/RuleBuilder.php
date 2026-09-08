<?php

namespace App\Custom;

use Illuminate\Database\Eloquent\Builder;

class RuleBuilder
{
    public static function applyRules(Builder $query, $rules)
    {
        $rules = collect($rules);

        $includeRules = $rules->filter(fn ($rule) => !$rule->exclusion)->values();
        $excludeRules = $rules->filter(fn ($rule) => $rule->exclusion)->values();

        if ($includeRules->isNotEmpty()) {
            $query->where(function ($query) use ($includeRules) {
                foreach ($includeRules as $rule) {
                    self::applyRuleCondition($query, $rule);
                }
            });
        }

        if ($excludeRules->isNotEmpty()) {
            $query->where(function ($query) use ($excludeRules) {
                foreach ($excludeRules as $rule) {
                    self::applyRuleCondition($query, $rule);
                }
            });
        }
    }

    /**
     * Adiciona uma regra ao grupo (inclusão ou exclusão) da query.
     *
     * Regras de inclusão se combinam por OU entre si (a nota entra se
     * bater em qualquer uma). Regras de exclusão se combinam por E entre
     * si — cada uma já usa operador negado (!=, not like, not in), então
     * "E" é o combinador correto (nota só passa se não bater em NENHUMA
     * delas). Isso vale igual para regra simples ou com cláusula E (2ª
     * condição): o par inteiro (primária + secundária) é tratado como
     * uma única unidade, sempre combinada com as outras regras pelo
     * mesmo operador do grupo em que está.
     */
    protected static function applyRuleCondition($query, $rule)
    {
        $primary = self::buildCondition($rule->column_search, $rule->condition, $rule->value, $rule->exclusion);

        if (!$primary) {
            return;
        }

        $secondary = self::buildCondition($rule->column_search2 ?? null, $rule->condition2 ?? null, $rule->value2 ?? null, $rule->exclusion2 ?? $rule->exclusion);

        $method = $rule->exclusion ? 'where' : 'orWhere';

        $query->$method(function ($q) use ($primary, $secondary) {
            self::applyAtom($q, $primary);

            if ($secondary) {
                self::applyAtom($q, $secondary);
            }
        });
    }

    protected static function applyAtom($query, array $atom): void
    {
        match ($atom['method']) {
            'whereIn'    => $query->whereIn($atom['column'], $atom['value']),
            'whereNotIn' => $query->whereNotIn($atom['column'], $atom['value']),
            default      => $query->where($atom['column'], $atom['operator'], $atom['value']),
        };
    }

    /**
     * @return array{column: string, method: string, operator?: string, value: mixed}|null
     */
    protected static function buildCondition(?string $column, ?string $condition, mixed $value, bool $exclusion): ?array
    {
        if (!$column || !$condition) {
            return null;
        }

        if (in_array($condition, ['Em', 'NaoEstaEm'], true)) {
            $list = self::parseList($value);

            if (!$list) {
                return null;
            }

            // "Em" quer IN quando a regra não é de exclusão; "NaoEstaEm" já nasce
            // negado, então quer o inverso. O checkbox Exclusão inverte de novo,
            // igual às outras condições — mantém o mesmo modelo mental pra todas.
            $wantsIn = $condition === 'Em' ? !$exclusion : $exclusion;

            return [
                'column' => $column,
                'method' => $wantsIn ? 'whereIn' : 'whereNotIn',
                'value'  => $list,
            ];
        }

        if ($value === null || $value === '') {
            return null;
        }

        return match ($condition) {
            'Exatamente'  => ['column' => $column, 'method' => 'where', 'operator' => $exclusion ? '!=' : '=', 'value' => (string) $value],
            'Diferente'   => ['column' => $column, 'method' => 'where', 'operator' => $exclusion ? '=' : '!=', 'value' => (string) $value],
            'Inicia por'  => ['column' => $column, 'method' => 'where', 'operator' => $exclusion ? 'not like' : 'like', 'value' => $value . '%'],
            'Termina por' => ['column' => $column, 'method' => 'where', 'operator' => $exclusion ? 'not like' : 'like', 'value' => '%' . $value],
            default       => ['column' => $column, 'method' => 'where', 'operator' => $exclusion ? 'not like' : 'like', 'value' => '%' . $value . '%'],
        };
    }

    /**
     * Aceita o valor já como array, como JSON (`["2","4"]`) ou como texto
     * separado por vírgula (`2, 4`) — o que vier da UI ou de dados antigos.
     * Nunca lança exceção: valor malformado só faz a condição ser ignorada.
     */
    protected static function parseList(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            $items = $raw;
        } else {
            $decoded = json_decode((string) $raw, true);
            $items   = is_array($decoded) ? $decoded : explode(',', (string) $raw);
        }

        $items = array_values(array_filter(
            array_map('trim', array_map('strval', $items)),
            fn ($v) => $v !== ''
        ));

        return $items ?: null;
    }
}
