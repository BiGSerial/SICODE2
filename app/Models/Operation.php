<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'operacao',
        'descOperacao',
        'inicioPlanejado',
        'fimPlanejado',
        'inicioReal',
        'fimReal',
        'status',
        'notaOv',
        'cenPlan',
        'cenTrab',
        'txtCenTrab',
    ];

    protected $casts = [
        'inicioPlanejado' => 'date',
        'fimPlanejado' => 'date',
        'inicioReal' => 'date',
        'fimReal' => 'date',
    ];

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }
}
