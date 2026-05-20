<?php

namespace App\Models\Legal;

use App\Enum\LegalDemandSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalSourceSnapshot extends Model
{
    use HasFactory;

    protected $table = 'legal_source_snapshots';

    protected $fillable = [
        'legal_demand_id',
        'import_batch_id',
        'source_type',
        'source_external_id',
        'source_case_number_normalized',
        'source_process_number_core',
        'source_entity_key',
        'source_occurrence_key',
        'source_hash',
        'raw_payload',
        'normalized_payload',
        'changed_fields',
        'seen_at',
    ];

    protected $casts = [
        'source_type' => LegalDemandSourceType::class,
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'changed_fields' => 'array',
        'seen_at' => 'datetime',
    ];

    public function legalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function importBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'import_batch_id');
    }
}
