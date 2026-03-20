<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'cycle_id',
        'user_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Cycle()
    {
        return $this->belongsTo(ProjectReviewCycle::class, 'cycle_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}

