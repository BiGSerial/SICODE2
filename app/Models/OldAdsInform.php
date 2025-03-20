<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldAdsInform extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'ads_id',
        'user',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }
}
