<?php

namespace App\Models\Legal;

use App\Enum\{LegalDemandInternalStatus, LegalDemandSourceType, LegalSourcePresenceStatus};
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LegalDemand extends Model
{
    use HasFactory;

    protected $table = 'legal_demands';

    protected $fillable = [
        'uuid',
        'legal_case_id',
        'source_type',
        'source_table',
        'source_version',
        'source_record_key',
        'source_record_key_strategy',
        'source_record_key_confidence',
        'source_case_number',
        'source_process_number',
        'source_installation_number',
        'source_entity_key',
        'source_hash',
        'needs_identity_review',
        'needs_status_review',
        'source_subject',
        'source_description',
        'source_status',
        'source_status_at',
        'source_status_group',
        'process_status_at_import',
        'requesting_area_name',
        'delegated_responsible_name',
        'delegated_by_name',
        'delegated_at',
        'source_decision_at',
        'source_end_at',
        'summary',
        'title',
        'requesting_responsible_name',
        'responsible_area_name',
        'source_due_at',
        'first_seen_at',
        'last_seen_at',
        'missing_since',
        'missing_count',
        'source_presence_status',
        'internal_status',
        'action_state',
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
        'raw_payload',
        'normalized_payload',
        'source_specific_payload',
    ];

    protected $casts = [
        'source_type'                => LegalDemandSourceType::class,
        'source_due_at'              => 'datetime',
        'source_status_at'           => 'datetime',
        'source_decision_at'         => 'datetime',
        'source_end_at'              => 'datetime',
        'delegated_at'               => 'datetime',
        'first_seen_at'              => 'datetime',
        'last_seen_at'               => 'datetime',
        'missing_since'              => 'datetime',
        'missing_count'              => 'integer',
        'source_presence_status'     => LegalSourcePresenceStatus::class,
        'internal_status'            => LegalDemandInternalStatus::class,
        'closed_at'                  => 'datetime',
        'external_closed_at'         => 'datetime',
        'needs_identity_review'      => 'boolean',
        'needs_status_review'        => 'boolean',
        'raw_payload'                => 'array',
        'normalized_payload'         => 'array',
        'source_specific_payload'    => 'array',
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
                LegalDemandInternalStatus::CLOSED_INTERNAL->value,
                LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
                LegalDemandInternalStatus::CANCELLED->value,
                LegalDemandInternalStatus::IGNORED->value,
            ]);
    }

    public function scopeReturnedForCorrection(Builder $query): Builder
    {
        return $query->where('internal_status', LegalDemandInternalStatus::RETURNED_FOR_CORRECTION->value);
    }

    public function scopeExternallyClosed(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotNull('external_closed_at')
                ->orWhereIn('source_status_group', ['closed_done', 'closed_cancelled']);
        });
    }

    public function scopeExternallyActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('external_closed_at')
                ->whereNotIn('source_status_group', ['closed_done', 'closed_cancelled']);
        });
    }

    public function isExternallyClosed(): bool
    {
        if ($this->external_closed_at !== null) {
            return true;
        }

        return in_array((string) $this->source_status_group, ['closed_done', 'closed_cancelled'], true);
    }

    public function externalStatusBadge(): array
    {
        $group = (string) ($this->source_status_group ?? 'unknown');

        if ($this->isExternallyClosed()) {
            return ['class' => 'bg-secondary text-white', 'icon' => 'bi-lock-fill'];
        }

        if ($group === 'unknown') {
            return ['class' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split'];
        }

        if (in_array($group, ['open_in_progress', 'open_delegated', 'open_redirected'], true)) {
            return ['class' => 'bg-success text-white', 'icon' => 'bi-check-circle'];
        }

        return ['class' => 'bg-light text-muted border', 'icon' => 'bi-dash-circle'];
    }

    public static function formatProcessNumber(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') {
            return null;
        }

        $digits = str_pad(substr($digits, -20), 20, '0', STR_PAD_LEFT);

        return sprintf(
            '%s-%s.%s.%s.%s.%s',
            substr($digits, 0, 7),
            substr($digits, 7, 2),
            substr($digits, 9, 4),
            substr($digits, 13, 1),
            substr($digits, 14, 2),
            substr($digits, 16, 4),
        );
    }

    public function getSourceProcessNumberMaskedAttribute(): ?string
    {
        return static::formatProcessNumber($this->source_process_number);
    }

    // Compat getters for legacy blades/components.
    public function getSubjectAttribute(): ?string
    {
        return $this->source_subject;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->source_description;
    }

    public function getOriginAreaNameAttribute(): ?string
    {
        return $this->requesting_area_name;
    }

    public function getTargetAreaNameAttribute(): ?string
    {
        return $this->responsible_area_name;
    }

    public function getTargetPersonNameAttribute(): ?string
    {
        return $this->delegated_responsible_name;
    }

    public function getLegalResponsibleNameAttribute(): ?string
    {
        return $this->delegated_responsible_name
            ?? $this->requesting_responsible_name
            ?? $this->legalCase?->process_manager;
    }

    public function getExternalStatusAttribute(): ?string
    {
        return $this->process_status_at_import;
    }

    public function getExternalFlowStatusAttribute(): ?string
    {
        return $this->source_status;
    }

    public function getSourceExecutedAtAttribute(): ?Carbon
    {
        return $this->source_decision_at;
    }

    public function getSourceChangedAtAttribute(): ?Carbon
    {
        return $this->source_status_at;
    }

    public function getSourceStartedAtAttribute(): ?Carbon
    {
        return $this->delegated_at;
    }

    public function getSourceAnalysisAtAttribute(): ?Carbon
    {
        return null;
    }
}
