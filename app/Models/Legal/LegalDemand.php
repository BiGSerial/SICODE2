<?php

namespace App\Models\Legal;

use App\Enum\LegalDemandInternalStatus;
use App\Enum\LegalDemandSourceType;
use App\Enum\LegalSourcePresenceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDemand extends Model
{
    use HasFactory;

    protected $table = 'legal_demands';

    protected $fillable = [
        'uuid',
        'legal_case_id',
        'source_type',
        'source_external_id',
        'source_record_key',
        'source_hash',
        'title',
        'description',
        'subject',
        'service_type',
        'external_status',
        'external_flow_status',
        'origin_area_name',
        'target_area_name',
        'target_person_name',
        'source_started_at',
        'source_due_at',
        'source_redirected_at',
        'first_seen_at',
        'last_seen_at',
        'missing_since',
        'source_presence_status',
        'internal_status',
        'priority',
        'risk_level',
        'controller_user_id',
        'current_assigned_user_id',
        'current_assigned_team_id',
        'closed_by',
        'closed_at',
        'closure_reason',
        'external_closed_at',
        'external_protocol',
        'external_closure_note',
        'raw_payload',
    ];

    protected $casts = [
        'source_type' => LegalDemandSourceType::class,
        'source_started_at' => 'datetime',
        'source_due_at' => 'datetime',
        'source_redirected_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'missing_since' => 'datetime',
        'source_presence_status' => LegalSourcePresenceStatus::class,
        'internal_status' => LegalDemandInternalStatus::class,
        'closed_at' => 'datetime',
        'external_closed_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function LegalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function Controller()
    {
        return $this->belongsTo(User::class, 'controller_user_id')->withTrashed();
    }

    public function CurrentAssignee()
    {
        return $this->belongsTo(User::class, 'current_assigned_user_id')->withTrashed();
    }

    public function ClosedBy()
    {
        return $this->belongsTo(User::class, 'closed_by')->withTrashed();
    }

    public function Assignments()
    {
        return $this->hasMany(LegalDemandAssignment::class);
    }

    public function Events()
    {
        return $this->hasMany(LegalDemandEvent::class)->orderBy('occurred_at');
    }

    public function Files()
    {
        return $this->hasMany(LegalDemandFile::class);
    }

    public function Comments()
    {
        return $this->hasMany(LegalDemandComment::class)->orderByDesc('created_at');
    }

    public function SourceSnapshots()
    {
        return $this->hasMany(LegalSourceSnapshot::class);
    }

    public function scopeWithoutResponsible(Builder $query): Builder
    {
        return $query->whereNull('current_assigned_user_id')
            ->whereNull('current_assigned_team_id');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('source_due_at')
            ->where('source_due_at', '<', now())
            ->whereNotIn('internal_status', [
                LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
                LegalDemandInternalStatus::CANCELLED->value,
                LegalDemandInternalStatus::IGNORED->value,
            ]);
    }

    public function scopeReturnedForCorrection(Builder $query): Builder
    {
        return $query->where('internal_status', LegalDemandInternalStatus::RETURNED_FOR_CORRECTION->value);
    }
}
