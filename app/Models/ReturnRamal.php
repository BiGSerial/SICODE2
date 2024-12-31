<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRamal extends Model
{
    use HasFactory;

    protected $fillable = [
        'ramal_report_id',
        'service_id',
        'user_id',
        'category',
        'text_obs',
    ];

    public function RamalReport()
    {
        return $this->belongsTo(RamalReport::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Service()
    {
        return $this->belongsTo(Service::class);
    }
}
