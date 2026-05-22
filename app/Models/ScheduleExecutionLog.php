<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleExecutionLog extends Model
{
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE = 'DONE';
    public const STATUS_FAIL = 'FAIL';
    public const STATUS_SKIPPED = 'SKIPPED';

    protected $fillable = [
        'event_hash',
        'command_label',
        'command',
        'expression',
        'status',
        'scheduled_at',
        'started_at',
        'finished_at',
        'duration_seconds',
        'exit_code',
        'process_id',
        'process_command',
        'stopped_at',
        'stop_signal',
        'exception_message',
        'skip_reason',
        'output_path',
        'without_overlapping',
        'run_in_background',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'stopped_at' => 'datetime',
        'process_id' => 'integer',
        'duration_seconds' => 'decimal:2',
        'without_overlapping' => 'boolean',
        'run_in_background' => 'boolean',
    ];
}
