<?php

namespace App\Custom;

use Illuminate\Database\Eloquent\Builder;

class ProductionQueryBuilder
{
    public const CONDITION_EXACT = 'Exatamente';
    public const CONDITION_ENDS_WITH = 'Termina por';
    public const CONDITION_STARTS_WITH = 'Inicia por';
    public const CONDITION_CONTAINS = 'Contem';

    public static function applyRules(Builder $query, iterable $rules): Builder
    {
        $collection = collect($rules);

        if ($collection->isEmpty()) {
            return $query;
        }

        $includeRules = $collection->filter(fn ($rule) => !(bool) ($rule->exclusion ?? false))->values();
        $excludeRules = $collection->filter(fn ($rule) => (bool) ($rule->exclusion ?? false))->values();

        if ($includeRules->isNotEmpty()) {
            $query->where(function (Builder $group) use ($includeRules) {
                $includeRules->each(function ($rule) use ($group) {
                    static::applyRuleCondition($group, $rule, false);
                });
            });
        }

        if ($excludeRules->isNotEmpty()) {
            $query->where(function (Builder $group) use ($excludeRules) {
                $excludeRules->each(function ($rule) use ($group) {
                    static::applyRuleCondition($group, $rule, true);
                });
            });
        }

        return $query;
    }

    protected static function applyRuleCondition(Builder $group, object $rule, bool $isExclusion): void
    {
        $primary = static::buildFieldCondition(
            (string) ($rule->column_search ?? ''),
            (string) ($rule->condition ?? static::CONDITION_CONTAINS),
            $rule->value ?? null,
            $isExclusion
        );

        if (!$primary) {
            return;
        }

        $secondary = static::buildSecondaryCondition($rule, $isExclusion);

        $group->orWhere(function (Builder $nested) use ($primary, $secondary) {
            static::applyAtomicCondition($nested, $primary);

            if ($secondary) {
                static::applyAtomicCondition($nested, $secondary);
            }
        });
    }

    protected static function buildSecondaryCondition(object $rule, bool $isExclusion): ?array
    {
        $column = trim((string) ($rule->column_search2 ?? ''));
        $condition = trim((string) ($rule->condition2 ?? ''));
        $hasValue = isset($rule->value2) && $rule->value2 !== '';

        if ($column === '' || $condition === '' || !$hasValue) {
            return null;
        }

        $secondaryExclusion = (bool) ($rule->exclusion2 ?? $isExclusion);

        return static::buildFieldCondition(
            $column,
            $condition,
            $rule->value2,
            $secondaryExclusion
        );
    }

    protected static function buildFieldCondition(string $column, string $condition, mixed $value, bool $isExclusion): ?array
    {
        $column = trim($column);
        if ($column === '' || is_null($value)) {
            return null;
        }

        $condition = trim($condition);

        return match ($condition) {
            static::CONDITION_EXACT => [
                'column' => $column,
                'operator' => $isExclusion ? '!=' : '=',
                'value' => (string) $value,
            ],
            static::CONDITION_ENDS_WITH => [
                'column' => $column,
                'operator' => $isExclusion ? 'not like' : 'like',
                'value' => '%' . (string) $value,
            ],
            static::CONDITION_STARTS_WITH => [
                'column' => $column,
                'operator' => $isExclusion ? 'not like' : 'like',
                'value' => (string) $value . '%',
            ],
            static::CONDITION_CONTAINS => [
                'column' => $column,
                'operator' => $isExclusion ? 'not like' : 'like',
                'value' => '%' . (string) $value . '%',
            ],
            default => [
                'column' => $column,
                'operator' => $isExclusion ? 'not like' : 'like',
                'value' => '%' . (string) $value . '%',
            ],
        };
    }

    protected static function applyAtomicCondition(Builder $query, array $condition): void
    {
        $query->where(
            (string) ($condition['column'] ?? ''),
            (string) ($condition['operator'] ?? '='),
            $condition['value'] ?? null
        );
    }
}
