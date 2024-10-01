<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Justify extends Model
{
    use HasFactory;

    protected $fillable = [
        'viability_id',
        'justify',
        'answer',
        'grant',
        'dismiss',
        'answer_at',
    ];

    public function viability()
    {
        return $this->belongsTo(Viability::class);
    }
}
