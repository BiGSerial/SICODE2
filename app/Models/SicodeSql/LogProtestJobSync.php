<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogProtestJobSync extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidor SQL
    protected $connection = 'sqlsrv2';

    protected $table = 'protest_jobs_sync';

    public $timestamps = false;

   protected $fillable = [
        'protest_job_id',
        'complaint_number',
        'protest_tipo_nota',
        'protest_codecodf',
        'protest_type',
        'measure_number',
        'med_id',
        'med_status_sist',
        'med_result',
        'dispatcher_name',
        'dispatcher_company_name',
        'owner_name',
        'owner_company_name',
        'closer_name',
        'closer_company_name',
        'priority_label',
        'status_label',
        'sent_at',
        'accepted_at',
        'started_at',
        'sla_due_at',
        'sla_breached_at',
        'finished_at',
        'closed_at',
        'close_reason',
        'notes_json',
        'is_advance',
        'confirmed',
        'confirmed_at',
        'synced_at',
        'updated_at',
    ];

    protected $casts = [
        'sent_at'         => 'datetime',
        'accepted_at'     => 'datetime',
        'started_at'      => 'datetime',
        'sla_due_at'      => 'datetime',
        'sla_breached_at' => 'datetime',
        'finished_at'     => 'datetime',
        'closed_at'       => 'datetime',
        'confirmed_at'    => 'datetime',
        'synced_at'       => 'datetime',
        'updated_at'      => 'datetime',

        'is_advance'      => 'boolean',
        'confirmed'       => 'boolean',
        'notes_json'      => 'array',
    ];
}
