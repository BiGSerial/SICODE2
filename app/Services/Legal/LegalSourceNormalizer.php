<?php

namespace App\Services\Legal;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class LegalSourceNormalizer
{
    public function normalizeProcessNumber(mixed $value): ?string
    {
        $normalized = $this->normalizeText($value);

        if ($normalized === null) {
            return null;
        }

        $compact = preg_replace('/\s+/u', '', $normalized) ?? $normalized;
        $digits = preg_replace('/\D+/', '', $compact) ?? '';

        if ($digits !== '') {
            if (strlen($digits) <= 20) {
                return str_pad($digits, 20, '0', STR_PAD_LEFT);
            }

            return $digits;
        }

        return mb_strtoupper(preg_replace('/[^\pL\pN]+/u', '', $compact) ?? $compact);
    }

    public function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if ($text === '' || $text === '-' || mb_strtolower($text) === 'null') {
            return null;
        }

        return $text;
    }

    public function parseExternalDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        $raw = trim((string) $value);

        if ($raw === '' || mb_strtolower($raw) === 'null' || $raw === '-') {
            return null;
        }

        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y',
            'Y-m-d H:i:s.u',
            'Y-m-d H:i:s',
            'Y-m-d',
            'Y-m-d\TH:i:sP',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $raw);
            } catch (\Throwable) {
                // tenta o próximo formato
            }
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
