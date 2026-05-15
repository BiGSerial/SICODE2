<?php

namespace App\Models\Legal;

use App\Enum\LegalDemandSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalImportBatch extends Model
{
    use HasFactory;

    protected $table = 'legal_import_batches';

    protected $fillable = [
        'source_type',
        'started_at',
        'finished_at',
        'total_rows',
        'new_rows',
        'updated_rows',
        'unchanged_rows',
        'missing_rows',
        'failed_rows',
        'status',
        'error_message',
    ];

    protected $casts = [
        'source_type' => LegalDemandSourceType::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function SourceSnapshots()
    {
        return $this->hasMany(LegalSourceSnapshot::class, 'import_batch_id');
    }
}
