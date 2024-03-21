<?php

namespace App\Models\Edp_depc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseOrder extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv1';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'tbld_usr_baseOrdens';

    protected $keyType = 'string';

    protected $guarded = ['*'];
}
