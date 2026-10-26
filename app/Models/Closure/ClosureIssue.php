<?php

namespace App\Models\Closure;

use App\Models\{Company, Order, User};
use Illuminate\Database\Eloquent\Model;

class ClosureIssue extends Model
{
    public const STATUS_OPEN       = 'OPEN';
    public const STATUS_REQUESTED  = 'REQUESTED';
    public const STATUS_RESPONDED  = 'RESPONDED';
    public const STATUS_VALIDATING = 'VALIDATING';
    public const STATUS_RETURNED   = 'RETURNED';
    public const STATUS_RESOLVED   = 'RESOLVED';

    // Pendência entre áreas (origem/destino em App\Services\Closure\ClosureArea). ISSUE exige retorno; RETURN é devolutiva direta.
    public const KIND_ISSUE  = 'ISSUE';
    public const KIND_RETURN = 'RETURN';

    public const SOURCE_MANUAL = 'MANUAL';

    protected $fillable = [
        'order_id',
        'closure_order_state_id',
        'closure_issue_category_id',
        'closure_issue_subcategory_id',
        'destination_company_id',
        'origin_area',
        'destination_area',
        'kind',
        'source',
        'opened_by',
        'status',
        'cycle',
        'blocking',
        'description',
        'requirements',
        'block_state_snapshot',
        'opened_at',
        'requested_at',
        'responded_at',
        'resolved_at',
    ];

    protected $casts = [
        'cycle'                => 'integer',
        'blocking'             => 'boolean',
        'requirements'         => 'array',
        'block_state_snapshot' => 'array',
        'opened_at'            => 'datetime',
        'requested_at'         => 'datetime',
        'responded_at'         => 'datetime',
        'resolved_at'          => 'datetime',
    ];

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

    public function State()
    {
        return $this->belongsTo(ClosureOrderState::class, 'closure_order_state_id');
    }

    public function Category()
    {
        return $this->belongsTo(ClosureIssueCategory::class, 'closure_issue_category_id');
    }

    public function Subcategory()
    {
        return $this->belongsTo(ClosureIssueSubcategory::class, 'closure_issue_subcategory_id');
    }

    public function DestinationCompany()
    {
        return $this->belongsTo(Company::class, 'destination_company_id')->withTrashed();
    }

    public function OpenedBy()
    {
        return $this->belongsTo(User::class, 'opened_by')->withTrashed();
    }

    public function Events()
    {
        return $this->hasMany(ClosureIssueEvent::class)->orderBy('created_at')->orderBy('id');
    }
}
