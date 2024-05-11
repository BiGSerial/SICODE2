<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reclaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'service_id',
        'production_id',
        'completed',
        'completed_at',
        'category',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Comments()
    {
        return $this->belongsToMany(Comment::class);
    }

    public function Viabilities()
    {
        return $this->belongsToMany(Viability::class);
    }

    public function Waiting()
    {
        return $this->hasOne(HiringWaiting::class);
    }
}
