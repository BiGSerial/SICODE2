<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'order_number',
        'total_cost',
        'company_cost',
        'client_cost',
        'sort_order',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'company_cost' => 'decimal:2',
        'client_cost' => 'decimal:2',
    ];

    public function Cycle()
    {
        return $this->belongsTo(ProjectReviewCycle::class, 'cycle_id');
    }
}
