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
        'source_table',
        'source_version',
        'started_at',
        'finished_at',
        'total_rows',
        'new_rows',
        'updated_rows',
        'unchanged_rows',
        'missing_rows',
        'returned_rows',
        'failed_rows',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'source_type' => LegalDemandSourceType::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function sourceSnapshots()
    {
        return $this->hasMany(LegalSourceSnapshot::class, 'import_batch_id');
    }

    public function events()
    {
        return $this->hasMany(LegalDemandEvent::class, 'import_batch_id');
    }
}
