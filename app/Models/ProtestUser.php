<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtestUser extends Model
{
    use HasFactory;

    protected $fillable = [

        'user_id',
        'default',
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function triggers()
    {
        return $this->hasMany(ProtestUserTrigger::class);
    }
}
