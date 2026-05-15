<?php

namespace App\Models\Legal;

use App\Enum\LegalDemandAssignmentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDemandAssignment extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_assignments';

    protected $fillable = [
        'uuid',
        'legal_demand_id',
        'assigned_by_user_id',
        'assigned_to_user_id',
        'assigned_to_team_id',
        'status',
        'message',
        'due_at',
        'sent_at',
        'received_at',
        'answered_at',
        'returned_at',
        'cancelled_at',
        'closed_at',
        'response_summary',
        'controller_review_note',
    ];

    protected $casts = [
        'status' => LegalDemandAssignmentStatus::class,
        'due_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'answered_at' => 'datetime',
        'returned_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function LegalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function AssignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id')->withTrashed();
    }

    public function AssignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id')->withTrashed();
    }

    public function Events()
    {
        return $this->hasMany(LegalDemandEvent::class, 'assignment_id')->orderBy('occurred_at');
    }

    public function Files()
    {
        return $this->hasMany(LegalDemandFile::class, 'assignment_id');
    }
}
