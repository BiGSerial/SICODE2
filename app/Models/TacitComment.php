<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TacitComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'viability_id',
        'user_id',
        'responsible_id',
        'justification',
        'response',
        'justified_at',
        'answered_at',
        'granted',
        'dismissed',
    ];

    protected $casts = [
        'justified_at' => 'datetime',
        'answered_at' => 'datetime',
        'granted' => 'boolean',
        'dismissed' => 'boolean',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function Viability()
    {
        return $this->belongsTo(Viability::class);
    }
}
