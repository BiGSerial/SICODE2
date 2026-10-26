<?php

namespace App\Services\Ads;

use App\Models\{AdsNonWorkingDayAdjustment, Holiday, WorkReport};
use App\Support\SicodeRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdsDeadlinePolicy
{
    public const ADS_DELIVERY_BUSINESS_DAYS = 3;
    public const DEFAULT_STATE              = 'ES';

    /** @var array<string, array<string, bool>> */
    private array $holidayCache = [];

    /** @var array<string, list<string>> feriados que caem em dia útil, por UF */
    private array $weekdayHolidays = [];

    /** @var array<int, array<string, bool>> */
    private array $adjustmentCache = [];

    public function dueAt(Carbon $informedAt, ?string $state = null, ?int $workReportId = null): Carbon
    {
        $date      = $informedAt->copy()->startOfDay();
        $remaining = SicodeRules::adsDeliveryDeadlineDays();

        while ($remaining > 0) {
            $date->addDay();

            if ($this->countsAsDeadlineDay($date, $state, $workReportId)) {
                $remaining--;
            }
        }

        return $date->endOfDay();
    }

    /**
     * Mantém work_reports.ads_due_at: prazo vigente de entrega da ADS (regra do deployment em SicodeRules).
     * Segue o informed_at atual (reinforme recalcula) e fica congelado quando já existe ADS tácita,
     * pois a partir daí o prazo oficial é adsforms.tacit_due_at. Grava por query para não disparar eventos.
     */
    public function syncWorkReport(WorkReport $workReport): ?Carbon
    {
        if ($workReport->Adsform()->where('tacit', true)->exists()) {
            return $workReport->ads_due_at;
        }

        $dueAt = $workReport->informed_at && !$workReport->rejected && !$workReport->canceled
            ? $this->dueAt($workReport->informed_at, $this->stateForWorkReport($workReport), (int) $workReport->id)
            : null;

        WorkReport::query()->whereKey($workReport->id)->update(['ads_due_at' => $dueAt]);
        $workReport->setAttribute('ads_due_at', $dueAt);
        $workReport->syncOriginalAttribute('ads_due_at');

        return $dueAt;
    }

    /**
     * Recalcula ads_due_at dos informes ainda sem ADS tácita (feriado novo, regra alterada, backfill).
     * Com $state, restringe aos informes de empresas daquele UF. Retorna quantos foram processados.
     */
    public function syncPending(?string $state = null, int $chunk = 500): int
    {
        $total = 0;

        WorkReport::query()
            ->whereNotNull('informed_at')
            ->where('canceled', false)
            ->where('rejected', false)
            ->whereDoesntHave('Adsform', fn ($q) => $q->where('tacit', true))
            ->with('Company.Address')
            ->chunkById($chunk, function (Collection $reports) use ($state, &$total) {
                $this->preloadAdjustments($reports->pluck('id'));

                foreach ($reports as $report) {
                    if ($state && $this->stateForWorkReport($report) !== $this->normalizeState($state)) {
                        continue;
                    }

                    $this->syncWorkReport($report);
                    $total++;
                }
            });

        return $total;
    }

    /** Dia que conta para o prazo: útil ou corrido, conforme SicodeRules (ads.delivery_deadline). */
    public function countsAsDeadlineDay(Carbon $date, ?string $state = null, ?int $workReportId = null): bool
    {
        return !SicodeRules::adsDeliveryDeadlineUsesBusinessDays()
            || $this->isBusinessDay($date, $state, $workReportId);
    }

    /** Carrega de uma vez os ajustes de dias não úteis, evitando uma consulta por informe. */
    public function preloadAdjustments(Collection $workReportIds): void
    {
        $ids = $workReportIds->filter()->unique()->values();

        foreach ($ids as $id) {
            $this->adjustmentCache[(int) $id] = [];
        }

        AdsNonWorkingDayAdjustment::query()
            ->whereIn('work_report_id', $ids)
            ->get(['work_report_id', 'date'])
            ->each(function ($row) {
                $this->adjustmentCache[(int) $row->work_report_id][Carbon::parse($row->date)->toDateString()] = true;
            });
    }

    public function isBusinessDay(Carbon $date, ?string $state = null, ?int $workReportId = null): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $state = $this->normalizeState($state);

        $this->holidayCache[$state] ??= Holiday::query()
            ->where('state', $state)
            ->pluck('date')
            ->mapWithKeys(fn ($holiday) => [Carbon::parse($holiday)->toDateString() => true])
            ->all();

        if (isset($this->holidayCache[$state][$date->toDateString()])) {
            return false;
        }

        if ($workReportId) {
            if (!isset($this->adjustmentCache[$workReportId])) {
                $this->preloadAdjustments(collect([$workReportId]));
            }

            return !isset($this->adjustmentCache[$workReportId][$date->toDateString()]);
        }

        return true;
    }

    public function lateDays(?Carbon $dueAt, ?Carbon $deliveredAt = null, ?Carbon $referenceAt = null, ?string $state = null, ?int $workReportId = null): int
    {
        if (!$dueAt) {
            return 0;
        }

        $end    = ($deliveredAt ?: ($referenceAt ?: now()))->copy()->startOfDay();
        $cursor = $dueAt->copy()->startOfDay()->addDay();

        if ($end->lt($cursor)) {
            return 0;
        }

        if (!SicodeRules::adsDeliveryDeadlineUsesBusinessDays()) {
            return $cursor->diffInDays($end) + 1;
        }

        return $this->businessDaysBetween($cursor, $end, $state, $workReportId);
    }

    /**
     * Dias úteis em [$from, $to] (inclusive) sem iterar dia a dia: semanas inteiras valem 5 dias,
     * o resto é percorrido e feriados/ajustes do informe dentro do intervalo são descontados.
     */
    private function businessDaysBetween(Carbon $from, Carbon $to, ?string $state, ?int $workReportId): int
    {
        $total = $from->diffInDays($to) + 1;
        $days  = intdiv($total, 7) * 5;
        $day   = $from->copy()->addDays(intdiv($total, 7) * 7);

        for ($i = 0; $i < $total % 7; $i++, $day->addDay()) {
            if (!$day->isWeekend()) {
                $days++;
            }
        }

        $state = $this->normalizeState($state);
        $this->holidayCache[$state] ??= Holiday::query()
            ->where('state', $state)
            ->pluck('date')
            ->mapWithKeys(fn ($holiday) => [Carbon::parse($holiday)->toDateString() => true])
            ->all();
        $this->weekdayHolidays[$state] ??= array_keys(array_filter(
            $this->holidayCache[$state],
            fn ($value, $date) => !Carbon::parse($date)->isWeekend(),
            ARRAY_FILTER_USE_BOTH
        ));

        $start = $from->toDateString();
        $end   = $to->toDateString();

        foreach ($this->weekdayHolidays[$state] as $date) {
            if ($date >= $start && $date <= $end) {
                $days--;
            }
        }

        if ($workReportId) {
            if (!isset($this->adjustmentCache[$workReportId])) {
                $this->preloadAdjustments(collect([$workReportId]));
            }

            foreach (array_keys($this->adjustmentCache[$workReportId]) as $date) {
                if ($date >= $start && $date <= $end && !isset($this->holidayCache[$state][$date]) && !Carbon::parse($date)->isWeekend()) {
                    $days--;
                }
            }
        }

        return $days;
    }

    public function penaltyPercentage(int $lateDays): float
    {
        return match (true) {
            $lateDays <= 0  => 0.0,
            $lateDays <= 10 => $lateDays * 0.5,
            default         => 10.0,
        };
    }

    public function penaltyBand(int $lateDays): string
    {
        return match (true) {
            $lateDays <= 0  => 'sem_atraso',
            $lateDays <= 10 => 'multa_diaria_0_5',
            default         => 'multa_fixa_10',
        };
    }

    public function stateForWorkReport(?WorkReport $workReport): string
    {
        $uf = $workReport?->Company?->Address?->first()?->uf;

        return $this->normalizeState($uf);
    }

    public function adjustmentsFor(Collection $workReportIds): Collection
    {
        return AdsNonWorkingDayAdjustment::query()
            ->whereIn('work_report_id', $workReportIds->filter()->unique()->values())
            ->orderBy('date')
            ->get()
            ->groupBy('work_report_id');
    }

    public function normalizeState(?string $state): string
    {
        $state = strtoupper(trim((string) $state));

        return preg_match('/^[A-Z]{2}$/', $state) ? $state : self::DEFAULT_STATE;
    }
}
