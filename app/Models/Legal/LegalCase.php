<?php

namespace App\Models\Legal;

use App\Models\Note;
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
        'installation_number',
        'installation_number_normalized',
        'process_status',
        'district',
        'company_name',
        'process_manager',
        'law_firm',
        'process_nature',
        'process_cause',
        'identity_key',
        'identity_strategy',
        'identity_confidence',
        'sources_seen',
        'first_seen_at',
        'last_seen_at',
        'last_import_batch_id',
        'raw_latest_payload',
        'normalized_latest_payload',
    ];

    protected $casts = [
        'sources_seen' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'raw_latest_payload' => 'array',
        'normalized_latest_payload' => 'array',
    ];

    public function demands()
    {
        return $this->hasMany(LegalDemand::class);
    }

    public function lastImportBatch()
    {
        return $this->belongsTo(LegalImportBatch::class, 'last_import_batch_id');
    }

    public function notes()
    {
        return $this->belongsToMany(Note::class, 'legal_case_note')
            ->withPivot(['id', 'linked_by', 'linked_at', 'context', 'created_at'])
            ->withTimestamps();
    }

    // Compat getters for legacy blades/components.
    public function getLegalResponsibleNameAttribute(): ?string
    {
        return $this->process_manager;
    }

    public function getLawFirmNameAttribute(): ?string
    {
        return $this->law_firm;
    }

    public function getMainOriginAreaAttribute(): ?string
    {
        return $this->district;
    }
}
