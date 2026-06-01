<?php

namespace App\Models\Legal;

use App\Enum\LegalDemandAssignmentStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegalDemandAssignment extends Model
{
    use HasFactory;

    protected $table = 'legal_demand_assignments';

    protected $fillable = [
        'uuid',
        'legal_demand_id',
        'from_user_id',
        'to_user_id',
        'to_team_id',
        'status',
        'sent_at',
        'received_at',
        'answered_at',
        'returned_at',
        'message',
        'answer',
        'metadata',
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

    protected $casts = [
        'status'      => LegalDemandAssignmentStatus::class,
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
        'answered_at' => 'datetime',
        'returned_at' => 'datetime',
        'metadata'    => 'array',
    ];

    public function legalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id')->withTrashed();
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'from_user_id')->withTrashed();
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id')->withTrashed();
    }

    public function events()
    {
        return $this->hasMany(LegalDemandEvent::class, 'assignment_id')->orderBy('occurred_at');
    }

    public function files()
    {
        return $this->hasMany(LegalDemandFile::class, 'assignment_id');
    }

    public function getDueAtAttribute(): ?Carbon
    {
        $raw = data_get($this->metadata, 'due_at');

        if (!$raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
