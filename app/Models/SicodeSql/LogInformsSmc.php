<?php

namespace App\Models\SicodeSql;

use App\Models\Production as ModelsProduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogInformsSmc extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_informs_smc';


    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'smc_id',
        'note',
        'company',
        'user',
        'date',
        'equipment',
        'connection',
        'observation',
        'retry',
        'created_in',
        'updated_in',
        'rejected',
        'rejected_at',
        'informed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'equipment'   => 'boolean',
        'connection'  => 'boolean',
        'retry'       => 'boolean',
        'rejected'    => 'boolean',
        'rejected_at' => 'datetime',
        'informed_at' => 'datetime',
        'created_in'  => 'datetime',
        'updated_in'  => 'datetime',
    ];


}
