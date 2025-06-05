<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Protest extends Model
{
    use HasFactory;

    protected $fillable = [
        'nota',
        'tipoNota',
        'dtAberturaNota',
        'dtConclusaoDesej',
        'cenPlan',
        'cidade',
        'statUsuar',
        'descCausa',
        'descSubCausa',
    ];

    protected $casts = [
        'dtAberturaNota' => 'date',
        'dtConclusaoDesej' => 'date',
    ];


    public function medProtests()
    {
        return $this->hasMany(MedProtest::class, 'protest_id');
    }

    public function Comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function assignments()
    {
        return $this->morphMany(UserAssignment::class, 'assignable');
    }


}
