<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerRolePermission extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'partner_role_id',
        'permission_key',
        'scope_type',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(PartnerRole::class, 'partner_role_id');
    }
}
