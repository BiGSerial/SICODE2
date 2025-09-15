<?php

namespace App\Models\SicodeSql;

use App\Models\Production as ModelsProduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;

    // conexão com 'sicode' no Servidoe SQL;
    protected $connection = 'sqlsrv2';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'log_productions';

    protected $fillable = [
        'production_id',
        'user',
        'company',
        'dispatch_by',
        'company_dispatch',
        'att_by',
        'company_att',
        'service',
        'note',
        'note_status',
        'status',
        'dispatch_at',
        'att_at',
        'stopped',
        'completed',
        'confirmed',
        'mmgd',
        'transfer',
        'conclusion',
        'input_manual',
        'conf_manual',
        'reje_manual',
        'dhstats',
        'eo',
        'iproject',
        'cadastro',
        'postes_u',
        'postes_c',
        'type_note',
        'centroTrab',
        'noinconsistency',
        'completed_at',
        'confirmed_at',
        'ma',
        'partial',
        'partial_at',
        'dfive'
    ];

    public function Productions()
    {
        return $this->belongsTo(ModelsProduction::class);
    }
}
