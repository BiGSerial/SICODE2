<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkReportFlowProduction extends Model
{
    public const STAGE_FISCALIZATION = 'fiscalization';
    public const STAGE_PAYMENT = 'payment';

    protected $fillable = [
        'work_report_id',
        'production_id',
        'stage',
        'is_current',
        'linked_at',
        'linked_by',
        'source',
        'metadata',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'linked_at' => 'datetime',
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
}
