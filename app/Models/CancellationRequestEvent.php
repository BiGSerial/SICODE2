<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationRequestEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancellation_request_id',
        'actor_id',
        'type',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function Request()
    {
        return $this->belongsTo(CancellationRequest::class, 'cancellation_request_id');
    }

    public function Actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
