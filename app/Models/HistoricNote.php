<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'old_date',
        'old_stat',
        'new_date',
        'new_stat',
    ];
}
