<?php

namespace App\Models\Closure;

use App\Models\User;
use Illuminate\Database\Eloquent\{Builder, Model};

class ClosureDepositCalendarEntry extends Model
{
    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_BLOCKED  = 'BLOCKED';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_SKIPPED  = 'SKIPPED';

    protected $table = 'closure_deposit_calendar';

    protected $fillable = [
        'year',
        'company_label',
        'city',
        'center',
        'deposit',
        'denomination',
        'service_type',
        'month',
        'inventory_responsible',
        'inventory_done',
        'scheduled_date',
        'status',
        'signal_id',
        'imported_by',
        'confirmed_by',
        'confirmed_at',
        'skip_reason',
    ];

    protected $casts = [
        'inventory_done' => 'boolean',
        'scheduled_date' => 'date',
        'confirmed_at'   => 'datetime',
    ];

    public function Signal()
    {
        return $this->belongsTo(ClosurePartnerSignal::class, 'signal_id');
    }

    public function ConfirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
