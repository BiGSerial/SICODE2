<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqlsrvJobLogSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'dt_run' => 'datetime',
        'dt_last_date' => 'datetime',
        'has_error' => 'boolean',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(SqlsrvHealthSnapshot::class, 'snapshot_id');
    }
}
