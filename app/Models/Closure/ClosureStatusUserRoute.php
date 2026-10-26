<?php

namespace App\Models\Closure;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Mapa de status usuário por serviço: o prefixo (2 primeiros tokens de orders.statusUser) pertence à caixa de UMA área. */
class ClosureStatusUserRoute extends Model
{
    protected $fillable = [
        'area',
        'status_user_prefix',
        'created_by',
    ];

    public function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
