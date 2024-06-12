<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeter extends Model
{
    use HasFactory;

    protected $fillable = [ // Campos preenchíveis em massa
        'work_report_id',
        'number',
        'borne',
        'fases'
    ];

    public function WorkReport()
    {
        return $this->belongsTo(WorkReport::class);
    }
}
