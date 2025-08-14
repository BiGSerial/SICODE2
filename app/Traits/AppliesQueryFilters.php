<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait AppliesQueryFilters
{
    /**
     * Aplica filtros genéricos em um Builder, a partir de um estado ($filtersState)
     * e um mapa de filtros ($map).
     *
     * @param  Builder $query
     * @param  array   $state  Ex: ['city'=>['SP','RJ'], 'search'=>'123', ...]
     * @param  array   $map    Regras por chave do state. Ver exemplos abaixo.
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $state, array $map): Builder
    {
        foreach ($map as $key => $rule) {
            if (!Arr::exists($state, $key)) {
                continue;
            }

            $value = $state[$key];

            // pular valores "vazios"
            if ($this->isEmptyFilter($value)) {
                continue;
            }

            // Regra por Closure (flexibilidade máxima)
            if ($rule instanceof \Closure) {
                $rule($query, $value, $state);
                continue;
            }

            // Regra por array (tipo + coluna + extras)
            if (is_array($rule)) {
                $type   = $rule['type']   ?? 'equals';
                $column = $rule['column'] ?? $key;

                switch ($type) {
                    case 'equals':
                        $query->where($column, $value);
                        break;

                    case 'in':
                        $query->whereIn($column, (array) $value);
                        break;

                    case 'like':
                        $likeValue = $this->normalizeLike($value, $rule['wildcard'] ?? true);
                        $query->where($column, 'like', $likeValue);
                        break;

                    case 'between_dates':
                        // $value = ['start' => 'Y-m-d', 'end' => 'Y-m-d']
                        $startCol = $rule['column_start'] ?? $column;
                        $endCol   = $rule['column_end']   ?? $column;

                        if (!empty($value['start'])) {
                            $query->where($startCol, '>=', $value['start'].' 00:00:00');
                        }
                        if (!empty($value['end'])) {
                            $query->where($endCol, '<=', $value['end'].' 23:59:59');
                        }
                        break;

                    case 'whereHas':
                        // relations + callback, ex:
                        // ['type'=>'whereHas','relation'=>'medProtests','callback'=>fn($q,$v)=> ...]
                        $relation = $rule['relation'] ?? null;
                        $cb       = $rule['callback'] ?? null;
                        if ($relation && $cb instanceof \Closure) {
                            $query->whereHas($relation, function ($q) use ($cb, $value, $state) {
                                $cb($q, $value, $state);
                            });
                        }
                        break;

                    case 'custom':
                        // callback direto, mas dentro de array
                        if (($rule['callback'] ?? null) instanceof \Closure) {
                            $rule['callback']($query, $value, $state);
                        }
                        break;
                }
            }
        }

        return $query;
    }

    protected function isEmptyFilter($value): bool
    {
        if (is_array($value)) {
            return count(array_filter($value, fn ($v) => $v !== '' && $v !== null)) === 0;
        }
        return $value === '' || $value === null;
    }

}
