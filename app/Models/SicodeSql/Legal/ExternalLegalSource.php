<?php

namespace App\Models\SicodeSql\Legal;

use Illuminate\Database\Eloquent\Model;

abstract class ExternalLegalSource extends Model
{
    protected function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        if ($text === '' || strtolower($text) === 'null' || $text === '-') {
            return null;
        }

        return $text;
    }

    protected function normalizeDigits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($value ?? '')) ?? '';

        return $digits !== '' ? $digits : null;
    }

    protected function normalizeCaseNumber(mixed $value): ?string
    {
        return $this->normalizeDigits($value);
    }

    protected function normalizeProcessNumber(mixed $value): ?string
    {
        return $this->normalizeDigits($value);
    }

    protected function processNumberCore(?string $normalizedProcessNumber): ?string
    {
        if ($normalizedProcessNumber === null) {
            return null;
        }

        return substr($normalizedProcessNumber, 0, 13) ?: null;
    }

    protected function normalizeServiceType(mixed $value): ?string
    {
        $normalized = $this->normalizeText($value);

        return $normalized !== null ? mb_strtoupper($normalized) : null;
    }

    protected function normalizedBasePayload(string $sourceType, array $payload): array
    {
        $caseNumber = $this->normalizeText($payload['case_number'] ?? null);
        $sourceProcessNumber = $this->normalizeText($payload['process_number'] ?? null);
        $caseNumberNormalized = $this->normalizeCaseNumber($caseNumber);
        $processNumberNormalized = $this->normalizeProcessNumber($sourceProcessNumber);

        return [
            'source_type' => $sourceType,
            'source_external_id' => $caseNumber,
            'case_number' => $caseNumber,
            'case_number_normalized' => $caseNumberNormalized,
            'source_process_number' => $sourceProcessNumber,
            'process_number_normalized' => $processNumberNormalized,
            'process_number_core' => $this->processNumberCore($processNumberNormalized),
        ];
    }
}
