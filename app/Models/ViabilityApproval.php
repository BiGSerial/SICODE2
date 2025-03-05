<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViabilityApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'approved',
        'tacit',
        'reason',
        'approved_at',
        'status',
        'dt_status',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'tacit' => 'boolean',
        'approved_at' => 'datetime',
        'dt_status' => 'datetime',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Reclaims()
    {
        return $this->belongsToMany(Reclaim::class, 'viability_approval_reclaim');
    }
}
