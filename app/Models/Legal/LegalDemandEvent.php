<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDemandEvent extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_events';

    protected $fillable = [
        'legal_demand_id',
        'assignment_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_user_id',
        'target_user_id',
        'target_team_id',
        'description',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public $timestamps = false;

    public function LegalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function Assignment()
    {
        return $this->belongsTo(LegalDemandAssignment::class, 'assignment_id');
    }

    public function Actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }

    public function TargetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id')->withTrashed();
    }
}
