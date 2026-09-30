<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityRejectionCategory extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'description', 'code', 'active', 'applies_project', 'applies_budget', 'sort_order'];

    protected $casts = ['active' => 'boolean', 'applies_project' => 'boolean', 'applies_budget' => 'boolean'];
    public function Parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function Children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    public function Items()
    {
        return $this->hasMany(QualityRejectionItem::class, 'category_id');
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where($type === 'PROJECT' ? 'applies_project' : 'applies_budget', true);
    }
}
