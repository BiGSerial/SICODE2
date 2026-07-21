<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalOrganRelease extends Model
{
    use HasFactory;

    public const TRACKED_STATUSES = [47, 48, 49, 50];
    public const HIRING_BLOCK_STATUSES = [47, 48, 49, 50, 51];
    public const RELEASE_STATUSES = [20, 11];
    public const EXCLUDED_CONCLUSIONS = [
        'ARQUIVADO',
        'LIBERACAO AUTOCAD ORGAO EXTERNO',
    ];

    protected $fillable = [
        'note_id',
        'production_id',
        'detected_nstats',
        'detected_dt_status',
        'exported_at',
        'exported_by',
        'released_at',
        'release_dt_status',
        'release_detected_at',
        'release_nstats',
        'released_by',
    ];

    protected $casts = [
        'detected_dt_status' => 'datetime',
        'exported_at' => 'datetime',
        'released_at' => 'datetime',
        'release_dt_status' => 'datetime',
        'release_detected_at' => 'datetime',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function exportedBy()
    {
        return $this->belongsTo(User::class, 'exported_by')->withTrashed();
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by')->withTrashed();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('released_at');
    }

    public function scopeNewForBadge(Builder $query): Builder
    {
        return $query->pending()
            ->whereNull('exported_at')
            ->eligibleForExternalOrganList()
            ->whereHas('note', function ($q) {
                $q->where('type_note', 2)
                    ->whereIn('nstats', self::TRACKED_STATUSES);
            });
    }

    public function scopeEligibleForExternalOrganList(Builder $query): Builder
    {
        return $query
            ->whereHas('production', function ($q) {
                $q->where(function ($productionQuery) {
                    $productionQuery
                        ->whereNull('d5')
                        ->orWhere('d5', false);
                })
                    ->whereDoesntHave('Analise', function ($analiseQuery) {
                        $analiseQuery->whereIn('conclusion', self::EXCLUDED_CONCLUSIONS);
                    });
            });
    }
}
