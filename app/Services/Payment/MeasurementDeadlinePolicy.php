<?php

namespace App\Services\Payment;

use App\Models\Holiday;
use App\Support\SicodeRules;
use Carbon\Carbon;

class MeasurementDeadlinePolicy
{
    private array $holidaysByDate = [];

    public function dueAt(Carbon $informedAt): Carbon
    {
        $date = $informedAt->copy()->startOfDay();

        if (!SicodeRules::paymentMeasurementDeadlineUsesBusinessDays()) {
            return $date->addDays(SicodeRules::paymentMeasurementDeadlineDays());
        }

        $remaining = SicodeRules::paymentMeasurementDeadlineDays();

        while ($remaining > 0) {
            $date->addDay();

            if ($this->isBusinessDay($date)) {
                $remaining--;
            }
        }

        return $date;
    }

    public function remainingDays(Carbon $dueAt, ?Carbon $referenceAt = null): int
    {
        $referenceAt = ($referenceAt ?: now())->copy()->startOfDay();
        $dueAt       = $dueAt->copy()->startOfDay();

        if (!SicodeRules::paymentMeasurementDeadlineUsesBusinessDays()) {
            return $referenceAt->diffInDays($dueAt, false);
        }

        if ($referenceAt->equalTo($dueAt)) {
            return 0;
        }

        $direction = $referenceAt->lt($dueAt) ? 1 : -1;
        $cursor    = $referenceAt->copy();
        $days      = 0;

        while (!$cursor->equalTo($dueAt)) {
            $cursor->addDay($direction);

            if ($this->isBusinessDay($cursor)) {
                $days += $direction;
            }
        }

        return $days;
    }

    public function statusForRemainingDays(int $remainingDays): string
    {
        return match (true) {
            $remainingDays < 0  => 'vencido',
            $remainingDays <= 4 => 'vencendo',
            default             => 'no_prazo',
        };
    }

    private function isBusinessDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $key = $date->toDateString();

        if (!array_key_exists($key, $this->holidaysByDate)) {
            $this->holidaysByDate[$key] = Holiday::query()
                ->where('state', strtoupper(SicodeRules::ruleset()))
                ->whereDate('date', $key)
                ->exists();
        }

        return !$this->holidaysByDate[$key];
    }
}
