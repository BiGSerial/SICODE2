<?php

namespace App\Models\Closure;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Pedido de transferência de Ordens entre dois usuários do Encerramento (aceite pelo destinatário; sem aprovação da gestão). */
class ClosureTransferRequest extends Model
{
    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_ACCEPTED  = 'ACCEPTED';
    public const STATUS_REJECTED  = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = ['from_user_id', 'to_user_id', 'status', 'reason', 'decision_note', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function From()
    {
        return $this->belongsTo(User::class, 'from_user_id')->withTrashed();
    }

    public function To()
    {
        return $this->belongsTo(User::class, 'to_user_id')->withTrashed();
    }

    public function Items()
    {
        return $this->hasMany(ClosureTransferRequestItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
