<?php

namespace App\Services\Legal;

use App\Enum\LegalDemandSubdemandStatus;
use App\Models\Legal\LegalDemand;

class LegalDemandSubdemandMetricsService
{
    public function refreshForDemand(LegalDemand $demand): void
    {
        $subdemands = $demand->subdemands()->get();

        $open = $subdemands->filter(function ($sub) {
            $status = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
            return !in_array($status, [LegalDemandSubdemandStatus::CONCLUIDA->value, LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR->value], true);
        });

        $overdue = $open->filter(fn ($sub) => $sub->deadline_at !== null && $sub->deadline_at->isPast());
        $dueToday = $open->filter(fn ($sub) => $sub->deadline_at !== null && $sub->deadline_at->isToday());
        $completed = $subdemands->filter(function ($sub) {
            $status = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
            return in_array($status, [LegalDemandSubdemandStatus::CONCLUIDA->value, LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR->value], true);
        });

        $avgResolutionSeconds = null;
        $durations = $completed
            ->filter(fn ($sub) => $sub->started_at !== null && $sub->finished_at !== null)
            ->map(fn ($sub) => max(0, $sub->finished_at->diffInSeconds($sub->started_at)));
        if ($durations->isNotEmpty()) {
            $avgResolutionSeconds = (int) round($durations->avg());
        }

        $slaStatus = 'no_subdemands';
        if ($overdue->isNotEmpty()) {
            $slaStatus = 'overdue';
        } elseif ($dueToday->isNotEmpty()) {
            $slaStatus = 'due_today';
        } elseif ($open->isNotEmpty()) {
            $slaStatus = 'on_time';
        }

        $criticality = 'low';
        if ($overdue->count() >= 2 || $open->count() >= 4) {
            $criticality = 'high';
        } elseif ($overdue->count() === 1 || $dueToday->isNotEmpty() || $open->count() >= 2) {
            $criticality = 'medium';
        }

        $demand->forceFill([
            'subdemand_open_count' => $open->count(),
            'subdemand_overdue_count' => $overdue->count(),
            'subdemand_completed_count' => $completed->count(),
            'subdemand_avg_resolution_seconds' => $avgResolutionSeconds,
            'subdemand_sla_status' => $slaStatus,
            'subdemand_criticality' => $criticality,
            'risk_level' => $criticality === 'high' ? 'high' : ($criticality === 'medium' ? 'medium' : ($demand->risk_level ?: 'low')),
        ])->save();
    }
}

