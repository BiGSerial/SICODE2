<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SqlsrvHealthSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'collected_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'job_logs_count' => 'integer',
        'metrics_count' => 'integer',
        'collection_errors' => 'array',
    ];

    public function jobLogs(): HasMany
    {
        return $this->hasMany(SqlsrvJobLogSnapshot::class, 'snapshot_id');
    }

    public function sourceMetrics(): HasMany
    {
        return $this->hasMany(SqlsrvSourceMetricSnapshot::class, 'snapshot_id');
    }
}
