<?php

namespace App\Models\Closure;

use App\Models\{Company, User};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosureOrderStatePeriod extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $fillable = [
        'closure_order_state_id',
        'operational_status',
        'holder_type',
        'holder_user_id',
        'holder_company_id',
        'started_at',
        'ended_at',
        'started_event_id',
        'ended_event_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function State()
    {
        return $this->belongsTo(ClosureOrderState::class, 'closure_order_state_id');
    }

    public function HolderUser()
    {
        return $this->belongsTo(User::class, 'holder_user_id')->withTrashed();
    }

    public function HolderCompany()
    {
        return $this->belongsTo(Company::class, 'holder_company_id')->withTrashed();
    }

    public function StartedEvent()
    {
        return $this->belongsTo(ClosureOrderEvent::class, 'started_event_id');
    }

    public function EndedEvent()
    {
        return $this->belongsTo(ClosureOrderEvent::class, 'ended_event_id');
    }
}
