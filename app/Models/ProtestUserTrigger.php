<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtestUserTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'protest_user_id',
        'user_id',
    ];

    public function protestUser()
    {
        return $this->belongsTo(ProtestUser::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
