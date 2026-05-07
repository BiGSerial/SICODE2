<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Reclaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'service_id',
        'production_id',
        'completed',
        'completed_at',
        'category',
        'subcategory_id',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('completed', false);
    }

    public static function hasActiveForService(int $noteId, string $serviceId): bool
    {
        return self::query()
            ->where('note_id', $noteId)
            ->where('service_id', $serviceId)
            ->active()
            ->exists();
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Comments()
    {
        return $this->belongsToMany(Comment::class);
    }

    public function Viabilities()
    {
        return $this->belongsToMany(Viability::class);
    }

    public function Waiting()
    {
        return $this->hasOne(HiringWaiting::class);
    }

    public function Approvals()
    {
        return $this->belongsToMany(ViabilityApproval::class, 'viability_approval_reclaim');
    }

    public function Externals()
    {
        return $this->belongsToMany(External::class, 'external_reclaim')->withPivot('completed', 'completed_at');
    }

    public function Subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function Files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable')->withTimestamps();
    }
}
