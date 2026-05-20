<?php

namespace App\Models\SicodeSql\Legal;

use Illuminate\Database\Eloquent\Builder;

class LegalSubsidy extends ExternalLegalSource
{
    protected $connection = 'sqlsrv2';

    protected $table = 'subjus_r3_subsidios';

    protected $primaryKey = 'case_number';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    public const SOURCE_TYPE = 'subsidy';

    public const NORMALIZED_COLUMNS = [
        'case_number',
        'process_number',
        'company_name',
        'process_status',
        'status_situation',
        'subsidy_subject',
        'subsidy_type',
        'observation',
        'analysis_at',
        'created_at',
        'deadline_at',
        'execution_at',
        'current_responsible_area',
        'current_responsible_name',
        'required_area',
        'requesting_responsible_name',
        'responsible_area',
        'opposing_party',
        'process_manager',
        'city',
        'region',
        'regional',
    ];

    public function scopeNormalized(Builder $query): Builder
    {
        return $query->select(self::NORMALIZED_COLUMNS);
    }

    public function toNormalizedArray(): array
    {
        $raw = $this->getAttributes();
        $base = $this->normalizedBasePayload(self::SOURCE_TYPE, $raw);

        return array_merge($base, [
            'company_name' => $this->normalizeText($raw['company_name'] ?? null),
            'external_status' => $this->normalizeText($raw['process_status'] ?? null),
            'external_flow_status' => $this->normalizeText($raw['status_situation'] ?? null),
            'subject' => $this->normalizeText($raw['subsidy_subject'] ?? null),
            'service_type' => $this->normalizeServiceType($raw['subsidy_type'] ?? null),
            'description' => $this->normalizeText($raw['observation'] ?? null),
            'source_analysis_at' => $raw['analysis_at'] ?? null,
            'source_started_at' => $raw['created_at'] ?? null,
            'source_due_at' => $raw['deadline_at'] ?? null,
            'source_executed_at' => $raw['execution_at'] ?? null,
            'source_changed_at' => $raw['execution_at'] ?? null,
            'origin_area_name' => $this->normalizeText($raw['required_area'] ?? null),
            'target_area_name' => $this->normalizeText($raw['current_responsible_area'] ?? null),
            'target_person_name' => $this->normalizeText($raw['current_responsible_name'] ?? null),
            'requesting_responsible_name' => $this->normalizeText($raw['requesting_responsible_name'] ?? null),
            'responsible_area_name' => $this->normalizeText($raw['responsible_area'] ?? null),
            'opposing_party' => $this->normalizeText($raw['opposing_party'] ?? null),
            'process_manager' => $this->normalizeText($raw['process_manager'] ?? null),
            'required_area' => $this->normalizeText($raw['required_area'] ?? null),
            'city' => $this->normalizeText($raw['city'] ?? null),
            'region' => $this->normalizeText($raw['region'] ?? null),
            'regional' => $this->normalizeText($raw['regional'] ?? null),
            'observation' => $this->normalizeText($raw['observation'] ?? null),
            'raw_payload' => $raw,
        ]);
    }
}
