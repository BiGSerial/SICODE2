<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkReportFlowProduction extends Model
{
    public const STAGE_FISCALIZATION = 'fiscalization';
    public const STAGE_PAYMENT = 'payment';
    public const SCOPE_GENERAL = 'general';
    public const SCOPE_NETWORK = 'network';
    public const SCOPE_CONNECTION = 'connection';

    protected $fillable = [
        'work_report_id',
        'production_id',
        'stage',
        'final_scope',
        'is_current',
        'linked_at',
        'linked_by',
        'reversed_at',
        'reversed_by',
        'reverse_reason',
        'source',
        'metadata',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'linked_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function WorkReport()
    {
        return $this->belongsTo(WorkReport::class);
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function LinkedBy()
    {
        return $this->belongsTo(User::class, 'linked_by')->withTrashed();
    }

    public function ReversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by')->withTrashed();
    }
}
