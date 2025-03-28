<?php

namespace App\Models\sicodesql;

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
        'user_name',
        'name',
        'obs',
        'contract',
        'center',
        'deposit',
        'amount',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];
}
