<?php

namespace App\Models\SicodeSql;

use App\Models\Production as ModelsProduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformLog extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_informs';

    protected $fillable = [
        'inform_id',
        'ordem',
        'note',
        'company',
        'user_name',
        'date',
        'equipment',
        'connection',
        'changes',
        'observation',
        'damage',
        'description',
        'team',
        'responsible',
        'approved',
        'rejected',
        'retry',
        'informed_at',
        'created_at',
        'updated_at',
        'first_at',
    ];

}
