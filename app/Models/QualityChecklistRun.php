<?php

namespace App\Models;

use App\Enum\QualityStageLevel;
use Illuminate\Database\Eloquent\Model;

class QualityChecklistRun extends Model
{
    protected $fillable = ['quality_process_id', 'quality_stage_id', 'quality_checklist_template_id', 'round_number', 'level', 'created_by', 'sent_at'];

    protected $casts = ['level' => QualityStageLevel::class, 'sent_at' => 'datetime'];

    public function Entries()
    {
        return $this->hasMany(QualityChecklistEntry::class)->orderBy('sort_order');
    }
    public function Process()
    {
        return $this->belongsTo(QualityProcess::class, 'quality_process_id');
    }
    public function Stage()
    {
        return $this->belongsTo(QualityStage::class, 'quality_stage_id');
    }
    public function Author()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
