<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'service',
        'dispatch',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }
}
