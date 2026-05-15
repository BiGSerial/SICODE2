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
        'source_record_key',
        'source_hash',
        'raw_payload',
        'seen_at',
    ];

    protected $casts = [
        'source_type' => LegalDemandSourceType::class,
        'raw_payload' => 'array',
        'seen_at' => 'datetime',
    ];

    public $timestamps = false;

    public function LegalDemand()
    {
        return $this->belongsTo(LegalDemand::class);
    }

    public function ImportBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'import_batch_id');
    }
}
