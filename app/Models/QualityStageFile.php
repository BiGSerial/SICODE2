<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityStageFile extends Model
{
    use HasFactory;

    protected $fillable = ['quality_stage_id', 'file_id', 'checksum', 'submitted_by', 'submitted_at'];

    protected $casts = ['submitted_at' => 'datetime'];
    public function Stage()
    {
        return $this->belongsTo(QualityStage::class, 'quality_stage_id');
    }
    public function File()
    {
        return $this->belongsTo(File::class);
    }
    public function SubmittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by')->withTrashed();
    }
}
