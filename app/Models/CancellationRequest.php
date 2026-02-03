<?php

namespace App\Models;

use App\Enum\CancellationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationRequest extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_ASSIGNED = 'ASSIGNED';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_DONE = 'DONE';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_ABORTED = 'ABORTED';

    public const SCOPE_NOTE_FULL = 'NOTE_FULL';
    public const SCOPE_ORDERS_PARTIAL = 'ORDERS_PARTIAL';

    public const CLOSURE_DONE = 'DONE';
    public const CLOSURE_REJECTED = 'REJECTED';
    public const CLOSURE_ABORTED = 'ABORTED';

    protected $fillable = [
        'note_id',
        'scope',
        'category_id',
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
        'status' => CancellationRequestStatus::class,
        'submitted_at' => 'datetime',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Category()
    {
        return $this->belongsTo(CancellationCategory::class, 'category_id');
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
        return $this->belongsToMany(Order::class, 'cancellation_request_orders')->withTimestamps();
    }

    public function Events()
    {
        return $this->hasMany(CancellationRequestEvent::class)->orderBy('created_at');
    }

    public function EvidenceFiles()
    {
        return $this->morphMany(EvidenceFile::class, 'evidenciable');
    }

    public function Comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at');
    }
}
