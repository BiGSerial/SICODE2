<?php

namespace App\Models\Closure;

use Illuminate\Database\Eloquent\Model;

class ClosureTransferRequestItem extends Model
{
    public const RESULT_MOVED   = 'MOVED';
    public const RESULT_SKIPPED = 'SKIPPED';

    protected $fillable = ['closure_transfer_request_id', 'closure_order_state_id', 'result', 'result_reason'];

    public function Request()
    {
        return $this->belongsTo(ClosureTransferRequest::class, 'closure_transfer_request_id');
    }

    public function State()
    {
        return $this->belongsTo(ClosureOrderState::class, 'closure_order_state_id');
    }
}
