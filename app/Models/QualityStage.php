<?php

namespace App\Models;

use App\Enum\{QualityStageKind, QualityStageLevel, QualityStageStatus, QualityStageType};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityStage extends Model
{
    use HasFactory;

    protected $fillable = ['quality_process_id', 'production_id', 'type', 'level', 'kind', 'status', 'round_number', 'assigned_user_id', 'dispatched_by', 'dispatched_at', 'executed_by', 'started_at', 'completed_at', 'approved_by', 'approved_at', 'observation', 'submission_data'];

    protected $casts = ['type' => QualityStageType::class, 'level' => QualityStageLevel::class, 'kind' => QualityStageKind::class, 'dispatched_at' => 'datetime', 'status' => QualityStageStatus::class, 'started_at' => 'datetime', 'completed_at' => 'datetime', 'approved_at' => 'datetime', 'submission_data' => 'array'];
    public function Process()
    {
        return $this->belongsTo(QualityProcess::class, 'quality_process_id');
    }
    public function AssignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id')->withTrashed();
    }
    public function DispatchedBy()
    {
        return $this->belongsTo(User::class, 'dispatched_by')->withTrashed();
    }
    public function ExecutedBy()
    {
        return $this->belongsTo(User::class, 'executed_by')->withTrashed();
    }
    public function ApprovedBy()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }
    public function Files()
    {
        return $this->hasMany(QualityStageFile::class);
    }
    public function Rejections()
    {
        return $this->hasMany(QualityRejection::class);
    }
    public function Events()
    {
        return $this->hasMany(QualityEvent::class);
    }
}
