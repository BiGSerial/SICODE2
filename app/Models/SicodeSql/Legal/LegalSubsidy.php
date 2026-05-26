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

    public function scopeNormalized(Builder $query): Builder
    {
        // Keep source extraction tolerant to column drift across SQL Server views/tables.
        return $query->select('*');
    }

    public function toNormalizedArray(): array
    {
        $raw = $this->getAttributes();
        $base = $this->normalizedBasePayload(self::SOURCE_TYPE, $raw);

        return array_merge($base, [
            'company_name' => $this->normalizeText($raw['company_name'] ?? null),
            'law_firm' => $this->normalizeText($raw['law_firm'] ?? $raw['law_firm_name'] ?? null),
            'district' => $this->normalizeText($raw['district'] ?? null),
            'process_nature' => $this->normalizeText($raw['process_nature'] ?? null),
            'process_cause' => $this->normalizeText($raw['process_cause'] ?? null),
            'external_status' => $this->normalizeText($raw['process_status'] ?? null),
            'external_flow_status' => $this->normalizeText($raw['status_situation'] ?? $raw['subsidy_status'] ?? null),
            'subject' => $this->normalizeText($raw['subsidy_subject'] ?? null),
            'service_type' => $this->normalizeServiceType($raw['subsidy_type'] ?? null),
            'description' => $this->normalizeText($raw['observation'] ?? null),
            'source_analysis_at' => $raw['analysis_at'] ?? null,
            'source_started_at' => $raw['created_at'] ?? null,
            'source_due_at' => $raw['return_deadline'] ?? $raw['deadline_at'] ?? null,
            'source_executed_at' => $raw['execution_at'] ?? null,
            'source_changed_at' => $raw['execution_at'] ?? null,
            'origin_area_name' => $this->normalizeText($raw['requesting_area'] ?? $raw['rquesting_area'] ?? $raw['required_area'] ?? null),
            'target_area_name' => $this->normalizeText($raw['current_responsible_area'] ?? null),
            'target_person_name' => $this->normalizeText($raw['current_responsible_name'] ?? null),
            'requesting_responsible_name' => $this->normalizeText($raw['requesting_responsible_name'] ?? null),
            'responsible_area_name' => $this->normalizeText($raw['responsible_area'] ?? null),
            'opposing_party' => $this->normalizeText($raw['opposing_party'] ?? null),
            'process_manager' => $this->normalizeText($raw['process_manager'] ?? null),
            'focal_point' => $this->normalizeText($raw['focal_point'] ?? null),
            'delegated_by' => $this->normalizeText($raw['delegated_by'] ?? null),
            'delegated_responsible_name' => $this->normalizeText($raw['delegated_responsible_name'] ?? null),
            'required_area' => $this->normalizeText($raw['required_area'] ?? null),
            'city' => $this->normalizeText($raw['city'] ?? null),
            'region' => $this->normalizeText($raw['region'] ?? null),
            'regional' => $this->normalizeText($raw['regional'] ?? null),
            'observation' => $this->normalizeText($raw['observation'] ?? null),
            'raw_payload' => $raw,
        ]);
    }
}
