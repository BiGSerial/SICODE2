<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqlsrvSourceMetricSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'row_count' => 'integer',
        'last_update_at' => 'datetime',
        'first_update_at' => 'datetime',
        'metric_payload' => 'array',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(SqlsrvHealthSnapshot::class, 'snapshot_id');
    }
}
