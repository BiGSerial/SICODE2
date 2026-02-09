<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAdsInforms extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'log_adsinforms';

    protected $fillable = [
        'adsform_id',
        'work_report_id',
        'note_id',
        'note',
        'user_name',
        'name',
        'obs',
        'contract',
        'center',
        'deposit',
        'amount',
        'tacit',
        'tacit_due_at',
        'tacit_delivered_at',
        'date',
    ];

    protected $casts = [
        'tacit' => 'boolean',
        'tacit_due_at' => 'datetime',
        'tacit_delivered_at' => 'datetime',
        'date' => 'datetime',
    ];
}
