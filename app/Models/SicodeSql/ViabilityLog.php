<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViabilityLog extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_viabilities';

    protected $fillable = [
        'viability_id',
        'hired_by',
        'company_hiring',
        'responsible',
        'company_responsible',
        'viability_by',
        'company_viability',
        'note',
        'order',
        'status',
        'completed',
        'approved',
        'rejected',
        'tacit',
        'hired',
        'completed_at',
        'returned_at',
        'hired_at',
        'tacit_at',
        'ri_sended_at',
        'ri_finished_at',
        'ri_service',
        'ri_category',
        'sended_at',
    ];
}
