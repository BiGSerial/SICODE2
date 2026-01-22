<?php

namespace App\Models;

use App\Enum\AdsRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        // UUIDs
        'requested_by' => 'string',
        'company_id'   => 'string',
        'batch_id'     => 'string',

        // ints
        'note_id'          => 'integer',
        'superseded_by_id' => 'integer',
        'sqlserver_id'     => 'integer',
        'attempts'         => 'integer',
        'version'          => 'integer',

        // enum + flags
        'status'    => AdsRequestStatus::class,
        'partner'   => 'boolean',
        'completed' => 'boolean',

        // dates
        'next_retry_at' => 'datetime',
        'canceled_at'   => 'datetime',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'delivered_at'  => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];


 
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'note_id');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }


}
