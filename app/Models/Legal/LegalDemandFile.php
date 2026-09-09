<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Storage, URL};

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
        'disk',
        'mime_type',
        'size',
        'sha256',
        'visibility',
        'removed_at',
        'removed_by',
    ];

    protected $casts = [
        'size'       => 'integer',
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

    public function storageDisk(): string
    {
        return $this->disk ?: 'public';
    }

    public function storageUrl(bool $download = false): ?string
    {
        if (!$this->path) {
            return null;
        }

        if (auth()->check()) {
            return route('legal.file.show', array_filter([
                'file'     => $this->id,
                'download' => $download ? 1 : null,
            ]));
        }

        return URL::temporarySignedRoute(
            'legal.external.file.show',
            now()->addHours(6),
            array_filter([
                'file'     => $this->id,
                'download' => $download ? 1 : null,
            ])
        );
    }

    public function legacyPublicUrl(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    public function downloadName(): string
    {
        return $this->original_name ?: $this->file_name ?: basename((string) $this->path);
    }
}
