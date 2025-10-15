<?php

namespace App\Support\Filters;

use App\Support\Filters\Contracts\AppliesToQuery;
use Illuminate\Database\Eloquent\Builder;

class InArray implements AppliesToQuery
{
    /**
     * @param string $columnOrRelation  Ex.: 'externals.status'  (coluna local)
     *                                  ou    'Entity.Type'      (relação/aninhada)
     * @param string|null $relatedColumn Coluna do modelo relacionado (ex.: 'id', 'name').
     *                                   Se null, aplica whereIn direto em $columnOrRelation.
     */
    public function __construct(
        private string $columnOrRelation,
        private ?string $relatedColumn = null
    ) {
    }

    public function apply(Builder $query, $value): Builder
    {
        $arr = is_array($value)
            ? array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''))
            : [];

        if (empty($arr)) {
            return $query;
        }

        // Modo RELAÇÃO: whereHas('Relacao[.SubRelacao]', fn($rel) => $rel->whereIn(coluna, $arr))
        if ($this->relatedColumn !== null) {
            $relationPath = $this->columnOrRelation;
            $column       = $this->relatedColumn;

            return $query->whereHas($relationPath, function (Builder $rel) use ($column, $arr) {
                // Se a coluna vier qualificada (tem ponto), respeitamos. Caso contrário, qualificamos com a tabela do related.
                $qualified = str_contains($column, '.') ? $column : ($rel->getModel()->getTable() . '.' . $column);
                $rel->whereIn($qualified, $arr);
            });
        }

        // Modo LOCAL: whereIn('tabela.coluna', $arr)
        return $query->whereIn($this->columnOrRelation, $arr);
    }
}
