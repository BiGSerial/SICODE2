<?php

namespace App\Models;

use App\Enum\CancellationRequestScope;
use App\Enum\CancellationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UncancellationRequest extends Model
{
    use HasFactory;

    public const CLOSURE_DONE = 'DONE';
    public const CLOSURE_REJECTED = 'REJECTED';
    public const CLOSURE_ABORTED = 'ABORTED';

    protected $fillable = [
        'note_id',
        'scope',
        'requested_by',
        'description',
        'status',
        'submitted_at',
        'assigned_to',
        'assigned_at',
        'closed_by',
        'closed_at',
        'closure_type',
        'closure_note',
    ];

    protected $casts = [
        'scope' => CancellationRequestScope::class,
        'status' => CancellationRequestStatus::class,
        'submitted_at' => 'datetime',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Requester()
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function Assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function Closer()
    {
        return $this->belongsTo(User::class, 'closed_by')->withTrashed();
    }

    public function Orders()
    {
        return $this->belongsToMany(Order::class, 'uncancellation_request_orders')->withTimestamps();
    }

    public function Events()
    {
        return $this->hasMany(UncancellationRequestEvent::class)->orderBy('created_at');
    }
}
