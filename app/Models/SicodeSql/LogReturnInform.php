<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogReturnInform extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'log_return_informs';

    protected $fillable = [
        'return_inform_id',
        'inform_id',
        'service',
        'usuario',
        'category',
        'text_obs',
        'returned_at',
        'created_at',
        'updated_at',
    ];

}
