<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferProdLog extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_transfer_prod';

    protected $fillable = [
        'prodtrans_id',
        'production_id',
        'note',
        'service',
        'from',
        'company_from',
        'to',
        'company_to',
        'info',
        'status',
        'note_status',
    ];
}
