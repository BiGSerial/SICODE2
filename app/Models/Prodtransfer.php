<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prodtransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'service_id',
        'from',
        'to',
        'info',
        'status',
        'read_to',
        'read_from',
    ];

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function From()
    {
        return $this->belongsTo(User::class, 'from', 'id')->withTrashed();
    }

    public function To()
    {
        return $this->belongsTo(User::class, 'to', 'id')->withTrashed();
    }
}
