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
        'case_number',
        'case_number_normalized',
        'process_number',
        'process_number_normalized',
        'process_number_core',
        'company_name',
        'external_status',
        'legal_responsible_name',
        'law_firm_name',
        'main_origin_area',
        'identity_key',
        'identity_strategy',
        'identity_confidence',
        'sources_seen',
        'first_seen_at',
        'last_seen_at',
        'last_import_batch_id',
    ];

    protected $casts = [
        'identity_confidence' => 'integer',
        'sources_seen' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function demands()
    {
        return $this->hasMany(LegalDemand::class);
    }

    public function lastImportBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'last_import_batch_id');
    }
}
