<?php

namespace App\Support\Filters;

use App\Support\Filters\Contracts\AppliesToQuery;
use App\Traits\WildcardFormatter;
use App\Traits\WildcardFormmater;
use Illuminate\Database\Eloquent\Builder;

class TextSearch implements AppliesToQuery
{
    use WildcardFormatter;

    public function __construct(private string $column = 'name', private ?int $minLen = 2)
    {
    }

    public function apply(Builder $query, $value): Builder
    {
        $term = trim((string)$value);
        if ($this->minLen && mb_strlen($term) < $this->minLen) {
            return $query;
        }

        $term = $this->formatWithWildcard($term);

        // Ajuste: se usar Postgres, troque para ILIKE.
        return $query->where($this->column, $term->type, $term->search);
    }
}
