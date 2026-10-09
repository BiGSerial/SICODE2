<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityChecklistItem extends Model
{
    protected $fillable = ['quality_checklist_section_id', 'code', 'question', 'severity_sp', 'severity_qa', 'active', 'sort_order'];

    protected $casts = ['active' => 'boolean'];
}
