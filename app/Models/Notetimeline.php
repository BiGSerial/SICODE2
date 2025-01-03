<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notetimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'service_id',
        'user_id',
        'info',
        'status',
        'system',
        'production_id',
        'return_stop',
        'category',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }
}
