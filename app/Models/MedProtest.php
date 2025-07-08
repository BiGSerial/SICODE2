<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedProtest extends Model
{
    use HasFactory;

    protected $fillable = [
        'protest_id',
        'med_id',
        'statusSist',
        'codMedida',
        'txtCodCodificacao',
        'txtCodMedida',
        'dtCriacaoMedida',
        'dtFimMedidaDesej',
        'dtFimMedida',
        'completed',
        'completed_at',
        'needsEvidence',
        'needsConfirmation',
    ];

    protected $casts = [
        'dtCriacaoMedida' => 'date',
        'dtFimMedidaDesej' => 'date',
        'dtFimMedida' => 'date',
        'completed_at' => 'datetime',
    ];

    public function Notes()
    {
        return $this->morphToMany(
            Note::class,
            'noteable',
        );
    }

    public function Protest()
    {
        return $this->belongsTo(Protest::class, 'protest_id');
    }

    public function Comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function Assignments()
    {
        return $this->morphMany(UserAssignment::class, 'assignable');
    }


}
