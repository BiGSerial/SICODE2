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

        $days = 0;
        while ($cursor->lte($end)) {
            if ($this->countsAsDeadlineDay($cursor, $state, $workReportId)) {
                $days++;
            }

            $cursor->addDay();
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
