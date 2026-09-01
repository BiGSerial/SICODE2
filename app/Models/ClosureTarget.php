<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosureTarget extends Model
{
    use HasFactory;

    public const ENTRY_RULE_EXCEPTION = 'atypical_manual_exception';

    protected $fillable = [
        'closure_cycle_id',
        'order_id',
        'note_id',
        'entry_rule',
        'entry_reference',
        'snapshot_status_sist',
        'frozen_at',
        'is_exception',
        'exception_reason',
        'requested_by',
        'authorized_by',
        'authorized_at',
    ];

    protected $casts = [
        'entry_reference' => 'array',
        'frozen_at'       => 'datetime',
        'is_exception'    => 'boolean',
        'authorized_at'   => 'datetime',
    ];

    public function Cycle()
    {
        return $this->belongsTo(ClosureCycle::class, 'closure_cycle_id');
    }

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function RequestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function AuthorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by')->withTrashed();
    }
}
