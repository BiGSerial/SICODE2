<?php

namespace App\Models;

use App\Enum\CancellationEngineerApprovalStatus;
use App\Enum\CancellationRequestStatus;
use App\Enum\CancellationRequestScope;
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
        'requires_engineer_approval',
        'engineer_approval_status',
        'engineer_approval_requested_by',
        'engineer_approval_requested_at',
        'engineer_approver_id',
        'engineer_approval_decided_by',
        'engineer_approval_decided_at',
        'engineer_approval_reason',
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
        'scope' => CancellationRequestScope::class,
        'requires_engineer_approval' => 'boolean',
        'engineer_approval_status' => CancellationEngineerApprovalStatus::class,
        'engineer_approval_requested_at' => 'datetime',
        'engineer_approval_decided_at' => 'datetime',
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

    public function EngineerApprovalRequester()
    {
        return $this->belongsTo(User::class, 'engineer_approval_requested_by')->withTrashed();
    }

    public function EngineerApprover()
    {
        return $this->belongsTo(User::class, 'engineer_approver_id')->withTrashed();
    }

    public function EngineerApprovalDecider()
    {
        return $this->belongsTo(User::class, 'engineer_approval_decided_by')->withTrashed();
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
