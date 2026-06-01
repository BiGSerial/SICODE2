<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDemandFile extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_files';

    protected $fillable = [
        'legal_demand_id',
        'assignment_id',
        'legal_demand_subdemand_id',
        'uploaded_by',
        'file_name',
        'original_name',
        'path',
        'mime_type',
        'size',
        'visibility',
        'removed_at',
        'removed_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'removed_at' => 'datetime',
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

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function removedBy()
    {
        return $this->belongsTo(User::class, 'removed_by')->withTrashed();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('removed_at');
    }
}
