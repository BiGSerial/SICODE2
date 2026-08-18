<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerAdminAuditEvent extends Model
{
    use HasFactory;
    use HasUuids;

    public const UPDATED_PERMISSIONS = 'updated_permissions';
    public const UPDATED_USER_BRANCHES = 'updated_user_branches';
    public const CREATED_USER = 'created_user';
    public const BULK_IMPORTED_USERS = 'bulk_imported_users';

    protected $fillable = [
        'company_id',
        'actor_user_id',
        'target_user_id',
        'event_type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
