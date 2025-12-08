<?php

namespace App\Models\Edp_depc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseD5 extends Model
{
    use HasFactory;

    // conexão com 'edp-depc';
    protected $connection = 'sqlsrv1';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'tbld_usr_baseD5';

    protected $keyType = 'string';

    protected $guarded = ['*'];
}
