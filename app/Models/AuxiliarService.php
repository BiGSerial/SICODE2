<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuxiliarService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'status',
        'column_search', 'condition', 'exclusion', 'value',
        'column_search2', 'condition2', 'exclusion2', 'value2',
    ];

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }
}
