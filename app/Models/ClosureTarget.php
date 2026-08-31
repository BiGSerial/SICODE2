<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosureTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'closure_cycle_id',
        'order_id',
        'note_id',
        'entry_rule',
        'entry_reference',
        'snapshot_status_sist',
        'frozen_at',
    ];

    protected $casts = [
        'entry_reference' => 'array',
        'frozen_at'       => 'datetime',
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
}
