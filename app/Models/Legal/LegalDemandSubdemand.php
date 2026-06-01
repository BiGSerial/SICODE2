<?php

namespace App\Models\Legal;

use App\Enum\LegalDemandSubdemandStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegalDemandSubdemand extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_subdemands';

    protected $fillable = [
        'uuid',
        'legal_demand_id',
        'assigned_to_user_id',
        'assigned_area_name',
        'status',
        'deadline_at',
        'started_at',
        'finished_at',
        'resolution',
        'created_by_user_id',
        'status_contract_version',
        'metadata',
        'external_access_token_hash',
        'external_access_expires_at',
        'external_access_revoked_at',
        'external_access_generated_by',
    ];

    protected $casts = [
        'status' => LegalDemandSubdemandStatus::class,
        'deadline_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
        'external_access_expires_at' => 'datetime',
        'external_access_revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function demand()
    {
        return $this->belongsTo(LegalDemand::class, 'legal_demand_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withTrashed();
    }

    public function events()
    {
        return $this->hasMany(LegalDemandSubdemandEvent::class, 'legal_demand_subdemand_id')->orderBy('occurred_at');
    }

    public function comments()
    {
        return $this->hasMany(LegalDemandComment::class, 'legal_demand_subdemand_id')->orderByDesc('created_at');
    }

    public function files()
    {
        return $this->hasMany(LegalDemandFile::class, 'legal_demand_subdemand_id')->whereNull('removed_at')->orderByDesc('created_at');
    }
}
