<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogExternalEntities extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'log_external_entities';
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'note',
        'type_note',
        'n_entities',
        'last_protocol',
        'dt_last_protocol',
        'sts_last_protocol',
        'last_entitie',
        'rubrica',
        'city',
        'pedido',
        'status',
        'last_update',
        'last_user',
        'dt_status',
        'dt_created',
        'situation',
        'completed',
        'created_at',    // agora mass-assignable
        'updated_at',    // idem
    ];


    protected $casts = [
        'dt_last_protocol' => 'datetime',
        'last_update'      => 'datetime',
        'dt_status'        => 'date',
        'dt_created'       => 'date',
        'completed'        => 'boolean',
    ];
}
