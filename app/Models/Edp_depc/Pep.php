<?php

namespace App\Models\Edp_depc;

use Illuminate\Database\Eloquent\Model;

class Pep extends Model
{
    protected $connection = 'sqlsrv1';

    // A tabela está em outro database da mesma instância SQL Server (DB1).
    protected $table = 'dbo.PEPs';

    protected $primaryKey = 'objeto';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = ['*'];
}
