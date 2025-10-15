<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;

class QueryFilterApplier
{
    public static function apply(Builder $base, array $map): Builder
    {
        foreach ($map as $entry) {
            $value  = $entry['value'] ?? null;
            $filter = $entry['filter'] ?? null;
            if ($filter && ($value !== null && $value !== '' && $value !== [])) {
                $base = $filter->apply($base, $value);
            }
        }
        return $base;
    }
}
