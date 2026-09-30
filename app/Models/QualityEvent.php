<?php

namespace App\Models;

use App\Enum\QualityEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityEvent extends Model
{
    use HasFactory;

    protected $fillable = ['quality_process_id', 'quality_stage_id', 'production_id', 'actor_id', 'actor_role', 'company_id', 'designer_id', 'target_user_id', 'type', 'from_state', 'to_state', 'stage_type', 'stage_level', 'round_number', 'observation', 'payload'];

    protected $casts = ['type' => QualityEventType::class, 'stage_type' => \App\Enum\QualityStageType::class, 'stage_level' => \App\Enum\QualityStageLevel::class, 'from_state' => \App\Enum\QualityProcessState::class, 'to_state' => \App\Enum\QualityProcessState::class, 'payload' => 'array'];
    public function Process()
    {
        return $this->belongsTo(QualityProcess::class, 'quality_process_id');
    }
    public function Stage()
    {
        return $this->belongsTo(QualityStage::class, 'quality_stage_id');
    }
    public function Actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
    public function Target()
    {
        return $this->belongsTo(User::class, 'target_user_id')->withTrashed();
    }
    public function Designer()
    {
        return $this->belongsTo(User::class, 'designer_id')->withTrashed();
    }
}
