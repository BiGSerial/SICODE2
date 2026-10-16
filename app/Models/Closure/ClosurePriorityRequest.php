<?php

namespace App\Models\Closure;

use App\Models\{Note, User};
use Illuminate\Database\Eloquent\Model;

class ClosurePriorityRequest extends Model
{
    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_DECLINED = 'DECLINED';

    protected $fillable = [
        'note_id',
        'requested_by',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected $casts = ['decided_at' => 'datetime'];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function RequestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function DecidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }
}
