<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manualconfirm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'production_id',
        'info',
    ];
}
