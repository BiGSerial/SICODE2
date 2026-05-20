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
        'source_case_number',
        'source_case_number_normalized',
        'source_process_number',
        'source_process_number_normalized',
        'source_process_number_core',
        'source_entity_key',
        'source_occurrence_key',
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
        'requesting_responsible_name',
        'responsible_area_name',
        'opposing_party',
        'process_manager',
        'required_area',
        'city',
        'region',
        'regional',
        'source_analysis_at',
        'source_started_at',
        'source_due_at',
        'source_executed_at',
        'source_changed_at',
        'first_seen_at',
        'last_seen_at',
        'missing_since',
        'missing_count',
        'source_presence_status',
        'internal_status',
        'priority',
        'risk_level',
        'controller_user_id',
        'current_assigned_user_id',
        'current_assigned_team_id',
        'last_seen_import_batch_id',
        'last_missing_batch_id',
        'last_returned_batch_id',
        'closed_by',
        'closed_at',
        'closure_reason',
        'external_closed_at',
        'external_protocol',
        'external_closure_note',
        'needs_identity_review',
        'source_identity_strategy',
        'source_identity_confidence',
        'raw_payload',
    ];

    protected $casts = [
        'source_type' => LegalDemandSourceType::class,
        'source_analysis_at' => 'datetime',
        'source_started_at' => 'datetime',
        'source_due_at' => 'datetime',
        'source_executed_at' => 'datetime',
        'source_changed_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'missing_since' => 'datetime',
        'missing_count' => 'integer',
        'source_presence_status' => LegalSourcePresenceStatus::class,
        'internal_status' => LegalDemandInternalStatus::class,
        'closed_at' => 'datetime',
        'external_closed_at' => 'datetime',
        'needs_identity_review' => 'boolean',
        'source_identity_confidence' => 'integer',
        'raw_payload' => 'array',
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function controller()
    {
        return $this->belongsTo(User::class, 'controller_user_id')->withTrashed();
    }

    public function currentAssignee()
    {
        return $this->belongsTo(User::class, 'current_assigned_user_id')->withTrashed();
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by')->withTrashed();
    }

    public function lastSeenImportBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'last_seen_import_batch_id');
    }

    public function lastMissingBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'last_missing_batch_id');
    }

    public function lastReturnedBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'last_returned_batch_id');
    }

    public function assignments()
    {
        return $this->hasMany(LegalDemandAssignment::class);
    }

    public function events()
    {
        return $this->hasMany(LegalDemandEvent::class)->orderBy('occurred_at');
    }

    public function files()
    {
        return $this->hasMany(LegalDemandFile::class);
    }

    public function comments()
    {
        return $this->hasMany(LegalDemandComment::class)->orderByDesc('created_at');
    }

    public function sourceSnapshots()
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
