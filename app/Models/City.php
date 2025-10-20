<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'rdMunicipio',
        'gpm',
        'cidade',
        'municipio',
        'respExpansao',
        'respPreventiva',
        'cenCusto',
        'baseConstrucao',
        'centrlizador',
        'centro',
        'regiao',
        'regional',
        'codIbge',
        'centroHana',
    ];

    public function Notes()
    {
        return $this->hasMany(Note::class, 'nexp', 'rdMunicipio');
    }

    public function Protests()
    {
        return $this->hasMany(Protest::class, 'cidade', 'cidade');
    }
}
