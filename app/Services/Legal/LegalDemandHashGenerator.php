<?php

namespace App\Services\Legal;

class LegalDemandHashGenerator
{
    public function make(array $payload): string
    {
        $parts = [
            $payload['source_type'] ?? null,
            $payload['source_external_id'] ?? null,
            $payload['process_number_normalized'] ?? null,
            $payload['external_status'] ?? null,
            $payload['legal_responsible_name'] ?? null,
            $payload['law_firm_name'] ?? null,
            $payload['origin_area_name'] ?? null,
            $payload['target_area_name'] ?? null,
            $payload['target_person_name'] ?? null,
            $payload['subject'] ?? null,
            $payload['description'] ?? null,
            $payload['source_started_at'] ?? null,
            $payload['source_due_at'] ?? null,
            $payload['external_flow_status'] ?? null,
        ];

        return hash('sha256', implode('|', $this->normalizeParts($parts)));
    }

    private function normalizeParts(array $parts): array
    {
        return array_map(
            static fn($value) => $value === null ? '' : (string) $value,
            $parts
        );
    }
}
