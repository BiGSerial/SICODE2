<?php

namespace App\Models\Closure;

use App\Models\{Order, User};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class ClosureOrderEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'closure_order_state_id',
        'order_id',
        'actor_id',
        'type',
        'origin',
        'before_data',
        'after_data',
        'meta',
        'batch_id',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data'  => 'array',
        'meta'        => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Eventos de encerramento são imutáveis.'));
        static::deleting(fn () => throw new LogicException('Eventos de encerramento são imutáveis.'));
    }

    public function State()
    {
        return $this->belongsTo(ClosureOrderState::class, 'closure_order_state_id');
    }

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

    public function Actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function StartedPeriods()
    {
        return $this->hasMany(ClosureOrderStatePeriod::class, 'started_event_id');
    }

    public function EndedPeriods()
    {
        return $this->hasMany(ClosureOrderStatePeriod::class, 'ended_event_id');
    }
}
