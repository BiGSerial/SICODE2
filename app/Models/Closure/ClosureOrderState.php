<?php

namespace App\Models\Closure;

use App\Models\{Company, Order, Production, User};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosureOrderState extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'production_id',
        'operational_status',
        'priority',
        'assigned_user_id',
        'current_holder_type',
        'current_holder_user_id',
        'current_holder_company_id',
        'entered_current_status_at',
        'last_event_id',
        'last_status_sist_seen',
        'last_status_sist_checked_at',
        'closed_at',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected $casts = [
        'priority'                    => 'boolean',
        'entered_current_status_at'   => 'datetime',
        'last_status_sist_checked_at' => 'datetime',
        'closed_at'                   => 'datetime',
        'cancelled_at'                => 'datetime',
    ];

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Assignee()
    {
        return $this->belongsTo(User::class, 'assigned_user_id')->withTrashed();
    }

    public function CurrentHolderUser()
    {
        return $this->belongsTo(User::class, 'current_holder_user_id')->withTrashed();
    }

    public function CurrentHolderCompany()
    {
        return $this->belongsTo(Company::class, 'current_holder_company_id')->withTrashed();
    }

    public function Events()
    {
        return $this->hasMany(ClosureOrderEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function Periods()
    {
        return $this->hasMany(ClosureOrderStatePeriod::class)->orderBy('started_at')->orderBy('id');
    }

    public function LastEvent()
    {
        return $this->belongsTo(ClosureOrderEvent::class, 'last_event_id');
    }

    public function Issues()
    {
        return $this->hasMany(ClosureIssue::class);
    }
}
