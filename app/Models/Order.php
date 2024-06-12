<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'ordem',
        'note_id',
        'descricao',
        'locInstalacao',
        'cenPlan',
        'prioridade',
        'statusSist',
        'statusUser',
        'cenTrab',
        'gpm',
        'custPlanejado',
        'custRealizado',
        'modifPor',
        'pep',
        'conjunto',
        'denConjunto',
        'dtEntrada',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Operations()
    {
        return $this->hasMany(Operation::class);
    }

    public function Viabilities()
    {
        return $this->hasMany(Viability::class);
    }

    public function WorkReports()
    {
        return $this->belongsToMany(WorkReport::class, 'order_work_report');
    }
}
