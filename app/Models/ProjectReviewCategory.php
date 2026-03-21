<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function Subcategories()
    {
        return $this->hasMany(ProjectReviewSubcategory::class, 'category_id');
    }
}
