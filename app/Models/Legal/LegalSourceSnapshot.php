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
        'source_record_key',
        'source_hash',
        'raw_payload',
        'normalized_payload',
        'source_specific_payload',
        'changed_fields',
        'seen_at',
    ];

    protected $casts = [
        'source_type' => LegalDemandSourceType::class,
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'source_specific_payload' => 'array',
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
