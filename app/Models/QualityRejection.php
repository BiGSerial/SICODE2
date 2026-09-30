<?php

namespace App\Models;

use App\Enum\{QualityStageLevel, QualityStageType};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityRejection extends Model
{
    use HasFactory;

    protected $fillable = ['quality_process_id', 'quality_stage_id', 'production_id', 'type', 'level', 'returned_to_level', 'round_number', 'author_id', 'designer_id', 'company_id', 'observation'];

    protected $casts = ['type' => QualityStageType::class, 'level' => QualityStageLevel::class, 'returned_to_level' => QualityStageLevel::class];

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
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }
    public function Designer()
    {
        return $this->belongsTo(User::class, 'designer_id')->withTrashed();
    }
    public function Items()
    {
        return $this->hasMany(QualityRejectionItem::class);
    }
}
