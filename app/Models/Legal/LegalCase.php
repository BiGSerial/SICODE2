<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalCase extends Model
{
    use HasFactory;

    protected $table = 'legal_cases';

    protected $fillable = [
        'uuid',
        'process_number',
        'process_number_normalized',
        'company_name',
        'external_status',
        'legal_responsible_name',
        'law_firm_name',
        'main_origin_area',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function Demands()
    {
        return $this->hasMany(LegalDemand::class);
    }
}
