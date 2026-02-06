<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adsform extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_report_id',
        'note_id',
        'user_id',
        'name',
        'obs',
        'contract',
        'center',
        'deposit',
        'amount',
        'partial',
        'tacit',
        'tacit_due_at',
        'tacit_delivered_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'partial' => 'boolean',
        'tacit' => 'boolean',
        'tacit_due_at' => 'datetime',
        'tacit_delivered_at' => 'datetime',
        'user_id' => 'string',
    ];

    public function workReport()
    {
        return $this->belongsTo(WorkReport::class);
    }

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function Files()
    {
        return $this->belongsToMany(File::class, 'adsforms_files');
    }
}
