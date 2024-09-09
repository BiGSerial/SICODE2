<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Daysviab extends Model
{
    use HasFactory;

    protected $fillable = [
        'viability_id',
        'user_id',
        'days',
        'reason'
    ];

    public function Viability()
    {
        return $this->belongsTo(Viability::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

}
