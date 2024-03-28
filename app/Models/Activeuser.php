<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activeuser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'watchdog'
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
