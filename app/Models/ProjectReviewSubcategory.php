<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewSubcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function Category()
    {
        return $this->belongsTo(ProjectReviewCategory::class, 'category_id');
    }

    public function Items()
    {
        return $this->hasMany(ProjectReviewItem::class, 'subcategory_id');
    }
}
