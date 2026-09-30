<?php

namespace App\Models;

use App\Enum\QualitySapRequestStatus;
use Illuminate\Database\Eloquent\Model;

class QualitySapRequest extends Model
{
    protected $fillable = ['quality_process_id', 'production_id', 'note_id', 'requested_by', 'driver', 'previous_status', 'target_status', 'status', 'attempt', 'response', 'error', 'requested_at', 'finished_at'];

    protected $casts = ['status' => QualitySapRequestStatus::class, 'response' => 'array', 'requested_at' => 'datetime', 'finished_at' => 'datetime'];

    public function Process()
    {
        return $this->belongsTo(QualityProcess::class, 'quality_process_id');
    }

    public function RequestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }
}
