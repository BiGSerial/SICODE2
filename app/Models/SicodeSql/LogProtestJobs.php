<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogProtestJobs extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'dbo.log_protest_jobs';

    protected $fillable = [
        'sicode_id',
        'batch_id',
        'note',
        'company',
        'status',
        'attempts',
        'partner',
        'register',
        'user',
        'email',
        'description',
        'url',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'partner' => 'boolean',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
