<?php

namespace App\Models\Edp_depc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseOperation extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv1';

    protected $table = 'tbld_usr_baseOperacoes';

    protected $keyType = 'string';

    protected $guarded = ['*'];
}
