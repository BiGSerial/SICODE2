<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDemandEvent extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_events';

    protected $fillable = [
        'legal_demand_id',
        'assignment_id',
        'import_batch_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_user_id',
        'target_user_id',
        'target_team_id',
        'description',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function legalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function assignment()
    {
        return $this->belongsTo(LegalDemandAssignment::class, 'assignment_id');
    }

    public function importBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'import_batch_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id')->withTrashed();
    }
}
