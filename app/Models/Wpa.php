<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wpa extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'note_id',
        'service_id',
        'dd',
        'sector',
        'workcenter',
        'stats',
        'execstats',
        'statuscomp',
        'ststusexec',
        'lat',
        'long',
        'desired_at',
        'issue_at',
        'completed_at',
    ];

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }
}
