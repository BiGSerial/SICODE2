<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileDownloadBatch extends Model
{
    protected $fillable = [
        'user_id', 'status', 'file_ids', 'output_pattern', 'disk', 'path',
        'download_name', 'error_message', 'expires_at', 'completed_at',
    ];

    protected $casts = [
        'file_ids' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
