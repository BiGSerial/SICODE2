<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Regra de elegibilidade do Pool: mesmo formato de AuxiliarService, aplicada por `RuleBuilder` sobre `notes`. */
class QualityPoolRule extends Model
{
    protected $fillable = ['column_search', 'condition', 'exclusion', 'value', 'column_search2', 'condition2', 'exclusion2', 'value2', 'created_by'];

    protected $casts = ['exclusion' => 'boolean', 'exclusion2' => 'boolean'];
}
