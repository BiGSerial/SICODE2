<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPartialsInforms extends Model
{
    use HasFactory;

    /**
     * Conexão com o banco SQL Server SICODE.
     */
    protected $connection = 'sqlsrv2';

    /**
     * Tabela de log das partials/informes.
     */
    protected $table = 'dbo.log_partials_informs';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'partial_id',
        'event_type',

        'note',
        'company_name',

        'user_name',
        'user_company_name',

        'engineer_name',
        'engineer_company_name',

        'supervision_name',
        'supervision_company_name',

        'payment_name',
        'payment_company_name',

        'observation',
        'engineer_info',

        'allow',
        'deny',
        'payment',
        'supervision',
        'complete',

        'responsible',
        'value',

        'decision_at',
        'payment_at',
        'supervision_at',

        'partial_created_at',
        'partial_updated_at',

        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'partial_id' => 'integer',

        'allow'       => 'boolean',
        'deny'        => 'boolean',
        'payment'     => 'boolean',
        'supervision' => 'boolean',
        'complete'    => 'boolean',

        'value' => 'decimal:2',

        'decision_at'    => 'datetime',
        'payment_at'     => 'datetime',
        'supervision_at' => 'datetime',

        'partial_created_at' => 'datetime',
        'partial_updated_at' => 'datetime',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const EVENT_SYNC = 'sync';
    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_ALLOWED = 'allowed';
    public const EVENT_DENIED = 'denied';
    public const EVENT_PAYMENT = 'payment';
    public const EVENT_SUPERVISION = 'supervision';
    public const EVENT_COMPLETED = 'completed';
}