<?php

namespace App\Services\WorkReports;

use App\Models\User;
use App\Models\WorkReportFlowProduction;
use Illuminate\Support\Facades\DB;

class WorkReportFlowProductionReversalService
{
    public function reverseScope(
        int $workReportId,
        string $finalScope,
        User|string|null $actor,
        string $reason,
        ?string $stage = null
    ): int {
        $actorId = $actor instanceof User ? $actor->id : $actor;

        return DB::transaction(function () use ($workReportId, $finalScope, $actorId, $reason, $stage): int {
            $query = WorkReportFlowProduction::query()
                ->where('work_report_id', $workReportId)
                ->where('final_scope', $finalScope)
                ->where('is_current', true);

            if ($stage) {
                $query->where('stage', $stage);
            }

            $updated = $query->update([
                'is_current' => false,
                'reversed_at' => now(),
                'reversed_by' => $actorId,
                'reverse_reason' => $reason,
            ]);

            if ($updated > 0) {
                app(WorkReportCurrentStatusRefresher::class)->refresh($workReportId);
            }

            return $updated;
        });
    }

    public function reverseProduction(
        int $productionId,
        string $finalScope,
        User|string|null $actor,
        string $reason,
        ?string $stage = null
    ): int {
        $actorId = $actor instanceof User ? $actor->id : $actor;

        return DB::transaction(function () use ($productionId, $finalScope, $actorId, $reason, $stage): int {
            $query = WorkReportFlowProduction::query()
                ->where('production_id', $productionId)
                ->where('final_scope', $finalScope)
                ->where('is_current', true);

            if ($stage) {
                $query->where('stage', $stage);
            }

            $workReportIds = (clone $query)->pluck('work_report_id')->unique()->values();

            $updated = $query->update([
                'is_current' => false,
                'reversed_at' => now(),
                'reversed_by' => $actorId,
                'reverse_reason' => $reason,
            ]);

            foreach ($workReportIds as $workReportId) {
                app(WorkReportCurrentStatusRefresher::class)->refresh((int) $workReportId);
            }

            return $updated;
        });
    }
}
