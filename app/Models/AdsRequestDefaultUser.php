<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsRequestDefaultUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'active',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'user_id' => 'string',
        'created_by' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
