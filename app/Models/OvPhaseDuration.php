<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvPhaseDuration extends Model
{
    public const SURVEY = 27;

    public const DESIGN = 28;

    protected $fillable = [
        'note_id',
        'phase',
        'started_at',
        'ended_at',
        'days',
        'company_id',
        'production_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'days'       => 'float',
    ];

    public function Note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function Company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }
}
