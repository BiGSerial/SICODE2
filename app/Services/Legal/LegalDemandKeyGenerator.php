<?php

namespace App\Services\Legal;

class LegalDemandKeyGenerator
{
    public function make(array $payload): string
    {
        $parts = [
            $payload['source_type'] ?? null,
            $payload['source_external_id'] ?? null,
            $payload['process_number_normalized'] ?? null,
            $payload['subject'] ?? null,
            $payload['source_started_at'] ?? null,
            $payload['source_redirected_at'] ?? null,
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
