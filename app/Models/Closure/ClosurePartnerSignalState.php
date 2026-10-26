<?php

namespace App\Models\Closure;

use Illuminate\Database\Eloquent\Model;

class ClosurePartnerSignalState extends Model
{
    public $timestamps = false;

    protected $fillable = ['closure_partner_signal_id', 'closure_order_state_id', 'previous_status', 'released_at'];

    protected $casts = ['released_at' => 'datetime'];

    public function Signal()
    {
        return $this->belongsTo(ClosurePartnerSignal::class, 'closure_partner_signal_id');
    }

    public function State()
    {
        return $this->belongsTo(ClosureOrderState::class, 'closure_order_state_id');
    }
}
