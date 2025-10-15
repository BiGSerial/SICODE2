<?php

namespace App\Support\Filters;

use App\Support\Filters\Contracts\AppliesToQuery;
use Illuminate\Database\Eloquent\Builder;

class ExactMatch implements AppliesToQuery
{
    public function __construct(private string $column)
    {
    }

    public function apply(Builder $query, $value): Builder
    {
        if ($value === null || $value === '') {
            return $query;
        }
        return $query->where($this->column, $value);
    }
}
