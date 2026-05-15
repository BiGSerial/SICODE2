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
        'user_id',
        'comment',
        'visibility',
    ];

    public function LegalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function Assignment()
    {
        return $this->belongsTo(LegalDemandAssignment::class, 'assignment_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
