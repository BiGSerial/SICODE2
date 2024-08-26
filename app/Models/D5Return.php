<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class D5Return extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'production_id',
        'user_id',
        'note',
        'reason',
        'description',
        'date',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
