<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringStatus extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_tohiring_status';


    protected $fillable = [
        'note_id',
        'note',
        'dt_status',
        'last_date',
        'position',
        'register',
        'responsible',
        'tacit',
        'tacit_at',
        'local',
        'rubrica',
    ];

    protected $casts = [
        'dt_status' => 'datetime',
        'last_date' => 'datetime',
        'tacit_at' => 'datetime',
        'tacit' => 'boolean',
    ];
}
