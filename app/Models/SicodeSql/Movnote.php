<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movnote extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_note_mov';

    protected $fillable = [
        'move_id',
        'production_id',
        'note',
        'service',
        'user',
        'company',
        'status',
        'note_status',
        'info',
        'stopped_at',
        'stopped_return',
    ];
}
