<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'note',
        'created_by',
        'dt_created',
        'dt_status',
        'user',
        'value',
        'currency',
        'eq_venda',
        'numPedido',
        'client',
        'group1',
        'group2',
        'group3',
        'group4',
        'group5',
        'pze',
        'num_material',
        'material',
        'nexp',
        'lexp',
        'pep',
        'nstats',
        'status',
        'days',
        'transaction',
        'validar_prazo',
        'rubrica',
        'pze_tratado',
        'days_stat',
        'pze_parecer',
        'days_left',
        'mmgd',
        'type_note',
        'centerjob',
        'doe',
        'postes',
    ];

    public function Productions()
    {
        return $this->hasMany(Production::class);
    }

    public function Historic()
    {
        return $this->hasMany(Notetimeline::class);
    }

    public function Wpas()
    {
        return $this->hasMany(Wpa::class);
    }

    public function Priorities()
    {
        return $this->hasMany(Priority::class);
    }

    public function Orders()
    {
        return $this->hasMany(Order::class);
    }
}
