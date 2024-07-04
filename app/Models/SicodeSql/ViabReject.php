<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViabReject extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'log_viab_rejected';

    protected $fillable = [
        'form_id',
        'order',
        'note',
        'responsible',
        'company',
        'reason',
        'description',
        'created_at',
        'updated_at'
    ];
}
