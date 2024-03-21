<?php

namespace App\Models\Edp_depc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wpaupdstatus extends Model
{
    use HasFactory;

    // conexão com 'edp-depc';
    protected $connection = 'sqlsrv1';

    protected $table = 'tbld_usr_baseDD';

    // protected $keyType = 'string';
    protected $guarded = ['*'];
}
