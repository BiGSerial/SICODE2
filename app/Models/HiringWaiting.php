<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringWaiting extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'reclaim_id',
        'category',
        'complete',
    ];


    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Reclaim()
    {
        return $this->belongsTo(Reclaim::class);
    }


}
