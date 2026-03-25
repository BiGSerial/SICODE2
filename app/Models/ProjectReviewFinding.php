<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewFinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'point_label',
        'subcategory_id',
        'item_id',
        'origin',
        'action_type',
        'quantity',
        'note',
    ];

    protected $casts = [
        'origin' => 'string',
        'quantity' => 'integer',
    ];

    public function Cycle()
    {
        return $this->belongsTo(ProjectReviewCycle::class, 'cycle_id');
    }

    public function Subcategory()
    {
        return $this->belongsTo(ProjectReviewSubcategory::class, 'subcategory_id');
    }

    public function Item()
    {
        return $this->belongsTo(ProjectReviewItem::class, 'item_id');
    }
}
