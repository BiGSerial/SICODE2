<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateExecutionLog extends Model
{
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE = 'DONE';
    public const STATUS_FAIL = 'FAIL';

    protected $fillable = [
        'task',
        'status',
        'options',
        'total',
        'updated',
        'created',
        'noteupdated',
        'erros',
        'errosMSGs',
        'fail_reason',
        'date_inicio',
        'date_fim',
        'failed_at',
    ];

    protected $casts = [
        'options' => 'array',
        'errosMSGs' => 'array',
        'date_inicio' => 'datetime',
        'date_fim' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
