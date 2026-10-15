<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityChecklistTemplate extends Model
{
    protected $fillable = ['name', 'version', 'active', 'created_by'];

    protected $casts = ['active' => 'boolean'];

    public function Sections()
    {
        return $this->hasMany(QualityChecklistSection::class)->orderBy('sort_order');
    }
}
