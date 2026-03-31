<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'round_number',
        'submitted_by',
        'submitted_at',
        'proportionality_ok',
        'proportionality_value',
        'decision',
        'decided_by',
        'decided_at',
        'analyst_note',
        'designer_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'proportionality_ok' => 'boolean',
        'proportionality_value' => 'decimal:2',
    ];

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Orders()
    {
        return $this->hasMany(ProjectReviewOrder::class, 'cycle_id')->orderBy('sort_order');
    }

    public function Findings()
    {
        return $this->hasMany(ProjectReviewFinding::class, 'cycle_id');
    }

    public function Messages()
    {
        return $this->hasMany(ProjectReviewMessage::class, 'cycle_id');
    }

    public function DecidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function SubmittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
