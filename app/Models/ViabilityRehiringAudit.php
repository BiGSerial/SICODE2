<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViabilityRehiringAudit extends Model
{
    use HasFactory;

    protected $guarded = []; // << resolve o erro

    protected $casts = [
        'was_newsend'       => 'boolean',
        'was_rehiring'      => 'boolean',
        'old_sended_at'     => 'datetime',
        'new_sended_at'     => 'datetime',
        'had_days_before'   => 'boolean',
        'days_count_before' => 'integer',
        'meta'              => 'array',
    ];

    public function viability(): BelongsTo
    {
        return $this->belongsTo(Viability::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    public function oldEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'old_engineer_id');
    }

    public function newEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_engineer_id');
    }

    public function oldCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'old_company_id');
    }

    public function newCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'new_company_id');
    }
}
