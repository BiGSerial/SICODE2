<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'needFile'
    ];

    protected $casts = [
        'needFile' => 'boolean',
    ];

    public function Category()
    {
        return $this->belongsTo(Category::class);
    }

    public function Reclaims()
    {
        return $this->hasMany(Reclaim::class);
    }
}
