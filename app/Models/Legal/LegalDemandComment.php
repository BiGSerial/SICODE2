<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDemandComment extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_comments';

    protected $fillable = [
        'legal_demand_id',
        'assignment_id',
        'legal_demand_subdemand_id',
        'user_id',
        'comment',
        'visibility',
    ];

    public function legalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function assignment()
    {
        return $this->belongsTo(LegalDemandAssignment::class, 'assignment_id');
    }

    public function subdemand()
    {
        return $this->belongsTo(LegalDemandSubdemand::class, 'legal_demand_subdemand_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
