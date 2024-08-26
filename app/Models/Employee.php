<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'service_id',
    ];

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function Service()
    {
        return $this->belongTo(Service::class);
    }
}
