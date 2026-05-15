<?php

namespace App\Models\Legal;

use App\Models\File;
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
        'file_id',
        'uploaded_by_user_id',
        'category',
        'visibility',
        'can_be_sent_external',
        'is_evidence',
        'is_final_response',
        'removed_at',
    ];

    protected $casts = [
        'can_be_sent_external' => 'boolean',
        'is_evidence' => 'boolean',
        'is_final_response' => 'boolean',
        'removed_at' => 'datetime',
    ];

    public function LegalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function Assignment()
    {
        return $this->belongsTo(LegalDemandAssignment::class, 'assignment_id');
    }

    public function File()
    {
        return $this->belongsTo(File::class);
    }

    public function UploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id')->withTrashed();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('removed_at');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }
}
