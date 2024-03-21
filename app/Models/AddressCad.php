<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressCad extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'production_id',
        'analise_id',
        'address',
        'district',
        'city',
        'cod',
        'exist',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Analise()
    {
        return $this->belongsTo(Analise::class);
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }
}
