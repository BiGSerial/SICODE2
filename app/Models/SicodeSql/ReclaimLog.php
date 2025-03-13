<?php

namespace App\Models\SicodeSql;

use App\Models\Production as ModelsProduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReclaimLog extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_reclaims';

    public $timestamps = true;

    protected $fillable = [
        'reclaim_id',
        'note',
        'origin',
        'category',
        'emissor',
        'company_emissor',
        'received_at',
        'att_at',
        'completed_at',
        'user',
        'company_user',
        'service',
    ];

}
