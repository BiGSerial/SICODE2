<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reclaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'service_id',
        'productions_id',
        'completed',
        'completed_at',
    ];
}
