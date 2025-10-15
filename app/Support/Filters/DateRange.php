<?php

namespace App\Support\Filters;

use App\Support\Filters\Contracts\AppliesToQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DateRange implements AppliesToQuery
{
    public function __construct(private string $column = 'created_at') {}

    public function apply(Builder $query, $value): Builder
    {
        if (!is_array($value)) return $query;
        $from = $value['from'] ?? null;
        $to   = $value['to']   ?? null;

        if ($from) $query->whereDate($this->column, '>=', Carbon::parse($from));
        if ($to)   $query->whereDate($this->column, '<=', Carbon::parse($to));
        return $query;
    }
}

