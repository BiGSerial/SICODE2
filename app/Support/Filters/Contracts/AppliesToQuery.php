<?php

namespace App\Support\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface AppliesToQuery
{
    public function apply(Builder $query, $value): Builder;
}
