<?php

namespace App\Services\Publication;

use App\Models\Holiday;
use App\Support\SicodeRules;
use Carbon\Carbon;

/**
 * Prazo para publicar após a entrega do INFORME (regra do deployment em `SicodeRules::publicationDeadlineDays()`: 2 dias por padrão,
 * corridos ou úteis). O dia da entrega é o dia 0; vence no fim do último dia.
 */
class PublicationDeadlinePolicy
{
    /** @var array<string, bool>|null feriados do estado como `Y-m-d => true` (uma consulta por instância) */
    private ?array $holidays = null;

    public function dueAt(Carbon $deliveredAt): Carbon
    {
        $date = $deliveredAt->copy()->startOfDay();
        $left = SicodeRules::publicationDeadlineDays();

        if (!SicodeRules::publicationDeadlineUsesBusinessDays()) {
            return $date->addDays($left)->endOfDay();
        }

        while ($left > 0) {
            $date->addDay();

            if ($this->isBusinessDay($date)) {
                $left--;
            }
        }

        return $date->endOfDay();
    }

    /** Dias que faltam (negativo = vencido há N dias), contando pelo calendário. */
    public function remainingDays(Carbon $dueAt, ?Carbon $referenceAt = null): int
    {
        return (int) ($referenceAt ?: now())->copy()->startOfDay()->diffInDays($dueAt->copy()->startOfDay(), false);
    }

    private function isBusinessDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $this->holidays ??= Holiday::query()
            ->where('state', strtoupper(SicodeRules::ruleset()))
            ->pluck('date')
            ->mapWithKeys(fn ($day) => [Carbon::parse($day)->toDateString() => true])
            ->all();

        return !isset($this->holidays[$date->toDateString()]);
    }
}
