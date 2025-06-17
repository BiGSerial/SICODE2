<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bancoupdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_update',
        'error',
        'inserts',
        'updates',
        'info',
    ];

    protected $casts = [
        'last_update' => 'datetime',
    ];
}
