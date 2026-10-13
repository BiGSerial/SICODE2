<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pep extends Model
{
    protected $primaryKey = 'objeto';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'objeto',
        'ano',
        'orcamento',
        'real',
        'compromisso',
        'plan_res_ord',
        'disposto',
        'disponivel',
        'regiao',
    ];

    protected $casts = [
        'ano'          => 'integer',
        'orcamento'    => 'decimal:2',
        'real'         => 'decimal:2',
        'compromisso'  => 'decimal:2',
        'plan_res_ord' => 'decimal:2',
        'disposto'     => 'decimal:2',
        'disponivel'   => 'decimal:2',
    ];
}
