<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationResp extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'note_id',
        'operacao',
        'confFinal',
        'fimReal',
        'fimLancado',
        'cenTrab',
        'txtCenTrab',
        'matriculaResp',
        'nomeResp',
    ];

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

}
