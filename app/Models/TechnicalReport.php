<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'med_protest_id',
        'title',
        'initial_content',
        'content',
        'report_date',
        'user_id',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    /**
     * Relacionamento com MedProtest
     */
    public function medProtest()
    {
        return $this->belongsTo(MedProtest::class);
    }

    /**
     * Relacionamento com User (autor do relatório)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
