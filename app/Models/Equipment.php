<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_report_id',
        'type',
        'patrimony',
        'fases',
        'pole',
        'installed'
    ];

    protected $casts = [
        'installed' => 'boolean',
        'type' => 'string',
        'patrimony' => 'string',
    ];

    public function WorkReport()
    {
        return $this->belongsTo(WorkReport::class);
    }
}
