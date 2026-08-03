<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'state',
        'year',
        'date',
        'name',
        'type',
        'is_banking_holiday',
        'source',
        'source_payload',
        'imported_at',
    ];

    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
        'is_banking_holiday' => 'boolean',
        'source_payload' => 'array',
        'imported_at' => 'datetime',
    ];
}
