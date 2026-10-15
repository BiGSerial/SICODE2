<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityChecklistSection extends Model
{
    protected $fillable = ['quality_checklist_template_id', 'name', 'sort_order'];

    public function Items()
    {
        return $this->hasMany(QualityChecklistItem::class)->orderBy('sort_order');
    }
}
