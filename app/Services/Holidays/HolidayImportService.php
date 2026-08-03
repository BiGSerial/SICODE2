<?php

namespace App\Services\Holidays;

use App\Infrastructure\Holidays\FeriadosApiClient;
use App\Models\Holiday;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HolidayImportService
{
    public function __construct(
        private FeriadosApiClient $client,
        private NormalizeImportedHoliday $normalizer
    ) {
    }

    public function preview(string $state, int $year): array
    {
        $state = $this->normalizeState($state);
        $response = $this->client->holidaysByState($state, $year);
        $holidays = $response['feriados'] ?? null;

        if (!is_array($holidays)) {
            throw new RuntimeException('Resposta da Feriados API sem a coleção feriados.');
        }

        $totalPages = (int) data_get($response, 'meta.total_pages', 1);
        if ($totalPages > 1) {
            throw new RuntimeException('A Feriados API retornou mais de uma página. Ajuste a importação antes de confirmar.');
        }

        return $this->uniqueRowsByDate(collect($holidays)
            ->map(fn (array $holiday) => $this->normalizer->handle($holiday, $state, $year))
            ->sortBy('date')
            ->values()
            ->all());
    }

    public function replaceCalendar(string $state, int $year, array $holidays): int
    {
        $state = $this->normalizeState($state);

        return DB::transaction(function () use ($state, $year, $holidays): int {
            Holiday::query()
                ->where('state', $state)
                ->where('year', $year)
                ->delete();

            $rows = collect($holidays)
                ->map(fn (array $holiday) => $this->normalizeForInsert($holiday, $state, $year))
                ->sortBy('date')
                ->values()
                ->all();

            $rows = $this->uniqueRowsByDate($rows);

            if (!empty($rows)) {
                Holiday::query()->insert($rows);
            }

            return count($rows);
        });
    }

    private function normalizeState(string $state): string
    {
        $state = strtoupper(trim($state));

        if (!preg_match('/^[A-Z]{2}$/', $state)) {
            throw new RuntimeException('UF inválida.');
        }

        return $state;
    }

    private function normalizeForInsert(array $holiday, string $state, int $year): array
    {
        if (isset($holiday['date'], $holiday['name'], $holiday['state'], $holiday['year'])) {
            $holiday['state'] = $state;
            $holiday['year'] = $year;
            $holiday['source_payload'] = is_string($holiday['source_payload'] ?? null)
                ? $holiday['source_payload']
                : json_encode($holiday['source_payload'] ?? $holiday, JSON_UNESCAPED_UNICODE);
            $holiday['imported_at'] = now()->toDateTimeString();
            $holiday['created_at'] = now()->toDateTimeString();
            $holiday['updated_at'] = now()->toDateTimeString();

            return $holiday;
        }

        return $this->normalizer->handle($holiday, $state, $year);
    }

    private function uniqueRowsByDate(array $rows): array
    {
        return collect($rows)
            ->unique(fn (array $holiday) => $holiday['date'])
            ->values()
            ->all();
    }
}
