<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityRejectionItem extends Model
{
    use HasFactory;

    protected $fillable = ['quality_rejection_id', 'category_id', 'subcategory_id', 'observation'];
    public function Rejection()
    {
        return $this->belongsTo(QualityRejection::class, 'quality_rejection_id');
    }
    public function Category()
    {
        return $this->belongsTo(QualityRejectionCategory::class, 'category_id');
    }
    public function Subcategory()
    {
        return $this->belongsTo(QualityRejectionCategory::class, 'subcategory_id');
    }
}
