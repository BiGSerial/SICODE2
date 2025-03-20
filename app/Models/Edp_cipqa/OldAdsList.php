<?php

namespace App\Models\Edp_cipqa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldAdsList extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv3';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'CIP_QA.dbo.tbl_detalhesADSenviadoParceira';

    protected $primaryKey = 'id';

    protected $fillable = [
        'Ov',
        'Nota',
        'Usuario',
        'Ordem',
        'Data',
    ];

    protected $casts = [
        'Data' => 'datetime',
    ];

    public $timestamps = false;

}
