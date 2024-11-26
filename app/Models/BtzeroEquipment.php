<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BtzeroEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ramal_report_id',
        'type',
        'installed',
        'patrimony',
        'fases',
        'pole',
    ];

    public function RamalReport()
    {
        return $this->belongsTo(RamalReport::class);
    }
}
