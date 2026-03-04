<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'five_note_id',
        'note_id',
        'event_type',
        'from_stage',
        'to_stage',
        'actor_user_id',
        'actor_role',
        'owner_user_id',
        'owner_role',
        'service_id',
        'production_id',
        'occurred_at',
        'inferred',
        'reason',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'inferred' => 'boolean',
        'metadata' => 'array',
    ];

    public function fiveNote(): BelongsTo
    {
        return $this->belongsTo(FiveNote::class);
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id')->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }
}

