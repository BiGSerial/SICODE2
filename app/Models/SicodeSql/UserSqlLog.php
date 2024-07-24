<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSqlLog extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_users';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'Registration',
        'email',
        'company',
        'superadm',
        'admin',
        'management',
        'operator',
        'user',
        'contract',
        'first_pass',
        'bypassprod',
        'responsible',
        'engineer',
        'onlyparner',
        'deleted',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
