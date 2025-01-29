<?php

namespace App\Models\Edp_depc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    // conexão com 'edp-depc';

    // protected $connection = 'sqlsrv1';
    // protected $table = 'tble_bov_bases';

    protected $primaryKey = 'rdMunicipio';

    protected $keyType = 'string';

    protected $guarded = ['*'];
}
