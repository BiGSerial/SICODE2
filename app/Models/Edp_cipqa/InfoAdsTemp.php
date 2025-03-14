<?php

namespace App\Models\Edp_cipqa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoAdsTemp extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv3';

    // protected $table = 'dbo.tbl_iw28_anexo';
    protected $table = 'CIP_QA.dbo.ADS_DIGITAL';
    protected $keyType = null;

    protected $guarded = ['*'];
}
