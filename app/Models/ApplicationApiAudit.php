<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ApplicationApiAudit extends Model
{
    protected $fillable = [
        'application_api_token_id',
        'created_by_user_id',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'http_status',
        'payload_hash',
        'records_received',
        'records_created',
        'records_updated',
        'operations_created',
        'operations_updated',
        'error_message',
        'summary',
    ];

    protected $casts = [
        'summary' => 'array',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(ApplicationApiToken::class, 'application_api_token_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
