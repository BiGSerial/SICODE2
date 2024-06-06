<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class External extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'entidade',
        'status',
        'completed',
    ];

    public function Protocols()
    {
        return $this->hasMany(Protocol::class);
    }

    public function Comments()
    {
        return $this->hasMany(ExternalComment::class);
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

}
