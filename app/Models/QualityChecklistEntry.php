<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityChecklistEntry extends Model
{
    protected $fillable = ['quality_checklist_run_id', 'template_item_id', 'section_name', 'code', 'question', 'severity', 'severity_qa', 'sort_order', 'status', 'observation', 'resolution', 'resolved_by', 'resolved_at', 'validated_by', 'validated_at'];

    protected $casts = ['resolved_at' => 'datetime', 'validated_at' => 'datetime'];
    public function Run()
    {
        return $this->belongsTo(QualityChecklistRun::class, 'quality_checklist_run_id');
    }
    public function ResolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by')->withTrashed();
    }
    public function ValidatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by')->withTrashed();
    }
}
