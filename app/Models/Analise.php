<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analise extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'ninst',
        'nMedidor',
        'patrimonio',
        'lat',
        'lon',
        'carga_ini',
        'carga_fim',
        'queda',
        'queda_max',
        'queda_cliente',
        'vao',
        'restricao',
        'motivo',
        'conclusion',
        'card',
        'info',
        'alimentador',
        'comprador',
        'matricula',
        'area',
        'documento',
        'endereco',
        'preresult',
        'doe',
        'postes',
        'protocol',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}
