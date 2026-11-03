<?php

namespace App\Services\Payment;

use App\Models\Holiday;
use App\Support\SicodeRules;
use Carbon\Carbon;

class MeasurementDeadlinePolicy
{
    /** Feriados do estado como conjunto `Y-m-d => true`, carregados numa consulta só (antes era 1 consulta por dia percorrido). */
    private ?array $holidaySet = null;

    private ?bool $usesBusinessDays = null;

    private ?int $deadlineDays = null;

    public function dueAt(Carbon $informedAt): Carbon
    {
        $date = $informedAt->copy()->startOfDay();

        if (!$this->usesBusinessDays()) {
            return $date->addDays($this->deadlineDays());
        }

        $remaining = $this->deadlineDays();

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

        if (!$this->usesBusinessDays()) {
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

    private function usesBusinessDays(): bool
    {
        return $this->usesBusinessDays ??= SicodeRules::paymentMeasurementDeadlineUsesBusinessDays();
    }

    private function deadlineDays(): int
    {
        return $this->deadlineDays ??= SicodeRules::paymentMeasurementDeadlineDays();
    }

    private function isBusinessDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $this->holidaySet ??= Holiday::query()
            ->where('state', strtoupper(SicodeRules::ruleset()))
            ->pluck('date')
            ->mapWithKeys(fn ($day) => [Carbon::parse($day)->toDateString() => true])
            ->all();

        return !isset($this->holidaySet[$date->toDateString()]);
    }
}
