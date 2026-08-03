<?php

namespace App\Services\Ads;

use App\Models\AdsNonWorkingDayAdjustment;
use App\Models\Holiday;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdsDeadlinePolicy
{
    public const ADS_DELIVERY_BUSINESS_DAYS = 3;
    public const DEFAULT_STATE = 'ES';

    public function dueAt(Carbon $informedAt, ?string $state = null, ?int $workReportId = null): Carbon
    {
        $date = $informedAt->copy()->startOfDay();
        $remaining = self::ADS_DELIVERY_BUSINESS_DAYS;

        while ($remaining > 0) {
            $date->addDay();

            if ($this->isBusinessDay($date, $state, $workReportId)) {
                $remaining--;
            }
        }

        return $date->endOfDay();
    }

    public function isBusinessDay(Carbon $date, ?string $state = null, ?int $workReportId = null): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $state = $this->normalizeState($state);

        $holiday = Holiday::query()
            ->where('state', $state)
            ->whereDate('date', $date->toDateString())
            ->exists();

        if ($holiday) {
            return false;
        }

        if ($workReportId) {
            return !AdsNonWorkingDayAdjustment::query()
                ->where('work_report_id', $workReportId)
                ->whereDate('date', $date->toDateString())
                ->exists();
        }

        return true;
    }

    public function lateDays(?Carbon $dueAt, ?Carbon $deliveredAt = null, ?Carbon $referenceAt = null, ?string $state = null, ?int $workReportId = null): int
    {
        if (!$dueAt) {
            return 0;
        }

        $end = ($deliveredAt ?: ($referenceAt ?: now()))->copy()->startOfDay();
        $cursor = $dueAt->copy()->startOfDay()->addDay();

        if ($end->lt($cursor)) {
            return 0;
        }

        $days = 0;
        while ($cursor->lte($end)) {
            if ($this->isBusinessDay($cursor, $state, $workReportId)) {
                $days++;
            }

            $cursor->addDay();
        }

        return $days;
    }

    public function penaltyPercentage(int $lateDays): float
    {
        return match (true) {
            $lateDays <= 0 => 0.0,
            $lateDays <= 10 => $lateDays * 0.5,
            default => 10.0,
        };
    }

    public function penaltyBand(int $lateDays): string
    {
        return match (true) {
            $lateDays <= 0 => 'sem_atraso',
            $lateDays <= 10 => 'multa_diaria_0_5',
            default => 'multa_fixa_10',
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
