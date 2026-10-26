<?php

namespace App\Models\Closure;

use App\Models\{Company, User};
use Illuminate\Database\Eloquent\{Builder, Model};

class ClosurePartnerSignal extends Model
{
    public const TYPE_DEPOSIT_BLOCKED = 'DEPOSIT_BLOCKED';

    public const ORIGIN_PARTNER  = 'PARTNER';
    public const ORIGIN_CALENDAR = 'CALENDAR';

    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_RELEASED = 'RELEASED';

    protected $fillable = [
        'company_id',
        'type',
        'origin',
        'deposit_code',
        'center',
        'deposit_label',
        'work_centers',
        'calendar_entry_id',
        'status',
        'reason',
        'release_note',
        'affected_count',
        'released_count',
        'created_by',
        'released_by',
        'released_at',
    ];

    protected $casts = [
        'work_centers'   => 'array',
        'affected_count' => 'integer',
        'released_count' => 'integer',
        'released_at'    => 'datetime',
    ];

    public function Company()
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function Author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function Releaser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function Items()
    {
        return $this->hasMany(ClosurePartnerSignalState::class, 'closure_partner_signal_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
