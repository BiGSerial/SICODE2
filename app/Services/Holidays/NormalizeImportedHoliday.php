<?php

namespace App\Services\Holidays;

use Carbon\CarbonImmutable;
use RuntimeException;

class NormalizeImportedHoliday
{
    public function handle(array $holiday, string $state, int $year): array
    {
        $rawDate = trim((string) ($holiday['data'] ?? $holiday['date'] ?? ''));
        $date = $this->parseDate($rawDate);

        if ((int) $date->format('Y') !== $year) {
            throw new RuntimeException("O feriado {$rawDate} não pertence ao ano {$year}.");
        }

        $name = trim((string) ($holiday['nome'] ?? $holiday['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException("Nome de feriado não informado para {$rawDate}.");
        }

        return [
            'external_id' => isset($holiday['id']) ? (string) $holiday['id'] : null,
            'date' => $date->format('Y-m-d'),
            'name' => $name,
            'type' => strtoupper(trim((string) ($holiday['tipo'] ?? $holiday['type'] ?? ''))),
            'state' => strtoupper($state),
            'year' => $year,
            'is_banking_holiday' => (bool) ($holiday['bancario'] ?? false),
            'source' => 'feriados_api',
            'source_payload' => json_encode($holiday, JSON_UNESCAPED_UNICODE),
            'imported_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    private function parseDate(string $rawDate): CarbonImmutable
    {
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $rawDate);
                if ($date && $date->format($format) === $rawDate) {
                    return $date;
                }
            } catch (\Throwable) {
                //
            }
        }

        throw new RuntimeException("Data de feriado inválida: {$rawDate}");
    }
}
