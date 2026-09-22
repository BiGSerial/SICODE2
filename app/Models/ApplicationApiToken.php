<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Model;

class ApplicationApiToken extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'name',
        'token_prefix',
        'token_hash',
        'active',
        'last_used_at',
        'last_used_ip',
        'revoked_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ApplicationApiAudit::class);
    }
}
