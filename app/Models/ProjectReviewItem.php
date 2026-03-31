<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcategory_id',
        'name',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function Subcategory()
    {
        return $this->belongsTo(ProjectReviewSubcategory::class, 'subcategory_id');
    }
}
