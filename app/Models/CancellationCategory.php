<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'active',
        'require_evidence',
        'min_evidence_files',
        'display_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'require_evidence' => 'boolean',
        'min_evidence_files' => 'integer',
        'display_order' => 'integer',
    ];

    public function requests()
    {
        return $this->hasMany(CancellationRequest::class, 'category_id');
    }
}
