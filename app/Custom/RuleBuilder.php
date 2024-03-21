<?php

namespace App\Custom;

use Illuminate\Database\Eloquent\Builder;

class RuleBuilder
{
    public static function applyRules(Builder $query, $rules)
    {

        // Verificar se há condições de não exclusão (exclusion = false)
        $nonExclusionRulesExist = collect($rules)->contains(function ($rule) {
            return !$rule->exclusion;
        });

        if ($nonExclusionRulesExist) {
            $query->Where(function ($query) use ($rules) {
                foreach ($rules as $rule) {
                    if (!$rule->exclusion) {
                        self::applyRuleCondition($query, $rule);
                    }
                }
            });
        }

        // Verificar se há condições de exclusão (exclusion = true)
        $exclusionRulesExist = collect($rules)->contains(function ($rule) {
            return $rule->exclusion;
        });

        if ($exclusionRulesExist) {
            $query->Where(function ($query) use ($rules) {
                foreach ($rules as $rule) {
                    if ($rule->exclusion) {
                        self::applyRuleCondition($query, $rule);
                    }
                }
            });
        }

    }

    protected static function applyRuleCondition($query, $rule)
    {
        $args = false;

        $columnName = $rule->column_search;
        $operator   = $rule->exclusion ? 'not like' : 'like';
        $value      = $rule->value;

        $condition = $rule->condition === 'Exatamente' ? $rule->exclusion ? '!=' : '=' : $operator;

        if ($rule->column_search2 && $rule->condition2 && $rule->value2) {
            $columnName2 = $rule->column_search2;
            $operator2   = $rule->exclusion2 ? 'not like' : 'like';
            $value2      = $rule->value2;

            $condition2 = $rule->condition2 === 'Exatamente' ? $rule->exclusion2 ? '!=' : '=' : $operator2;

            $args = true;
        }

        switch ($rule->condition) {
            case 'Exatamente':
                if ($args) {
                    $query->orWhere(function ($q) use ($columnName, $condition, $value, $columnName2, $condition2, $value2) {
                        return $q->Where($columnName, $condition, $value)->Where($columnName2, $condition2, $value2);
                    });
                } else {
                    if ($rule->exclusion || ($args && $rule->exclusion2)) {
                        $query->Where($columnName, $condition, $value);
                    } else {
                        $query->orWhere($columnName, $condition, $value);
                    }

                }

                break;
            case 'Termina por':
                if ($args) {
                    $query->orWhere(function ($q) use ($columnName, $condition, $value, $columnName2, $condition2, $value2) {
                        return $q->orWhere($columnName, $condition, '%' . $value)->Where($columnName2, $condition2, '%' . $value2);
                    });
                } else {

                    if ($rule->exclusion || ($args && $rule->exclusion2)) {
                        $query->Where($columnName, $condition, '%' . $value);
                    } else {
                        $query->orWhere($columnName, $condition, '%' . $value);
                    }
                }

                break;
            case 'Inicia por':
                if ($args) {
                    $query->orWhere(function ($q) use ($columnName, $condition, $value, $columnName2, $condition2, $value2) {
                        return $q->Where($columnName, $condition, $value . '%')->Where($columnName2, $condition2, $value2 . '%');
                    });
                } else {

                    $query->orWhere($columnName, $condition, $value . '%');
                }

                break;
            case 'Contem':
                if ($args) {
                    $query->orWhere(function ($q) use ($columnName, $condition, $value, $columnName2, $condition2, $value2) {
                        return $q->orWhere($columnName, $condition, '%' . $value . '%')->Where($columnName2, $condition2, '%' . $value2 . '%');
                    });
                } else {

                    if ($rule->exclusion || ($args && $rule->exclusion2)) {
                        $query->Where($columnName, $condition, '%' . $value . '%');
                    } else {
                        $query->orWhere($columnName, $condition, '%' . $value . '%');
                    }
                }

                break;
        }

    }
}
