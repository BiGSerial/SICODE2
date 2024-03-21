<?php

namespace App\Models\Edp_depc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseOV extends Model
{
    use HasFactory;

    // conexão com 'edp-depc';
    protected $connection = 'sqlsrv1';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'tbld_usr_baseOV';

    protected $keyType = 'string';

    protected $guarded = ['*'];
}
