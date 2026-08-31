<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UncancellationRequestEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uncancellation_request_id',
        'user_id',
        'event',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function Request()
    {
        return $this->belongsTo(UncancellationRequest::class, 'uncancellation_request_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
