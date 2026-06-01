<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDemandSubdemandEvent extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_subdemand_events';

    protected $fillable = [
        'legal_demand_subdemand_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_user_id',
        'actor_role',
        'reason',
        'description',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function subdemand()
    {
        return $this->belongsTo(LegalDemandSubdemand::class, 'legal_demand_subdemand_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }
}
