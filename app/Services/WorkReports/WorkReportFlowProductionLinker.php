<?php

namespace App\Services\WorkReports;

use App\Models\Production;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use Illuminate\Support\Facades\DB;

class WorkReportFlowProductionLinker
{
    public function linkFiscalization(Production $production, ?string $source = null, array $metadata = []): ?WorkReportFlowProduction
    {
        return $this->link($production, WorkReportFlowProduction::STAGE_FISCALIZATION, $source ?? 'dispatch_fiscalization', $metadata);
    }

    public function linkPayment(Production $production, ?string $source = null, array $metadata = []): ?WorkReportFlowProduction
    {
        return $this->link($production, WorkReportFlowProduction::STAGE_PAYMENT, $source ?? 'dispatch_payment', $metadata);
    }

    public function link(Production $production, string $stage, string $source, array $metadata = []): ?WorkReportFlowProduction
    {
        if ((bool) $production->partial) {
            return null;
        }

        $workReport = $this->resolveCurrentFinalWorkReport((int) $production->note_id);
        if (!$workReport) {
            return null;
        }

        return DB::transaction(function () use ($production, $workReport, $stage, $source, $metadata): WorkReportFlowProduction {
            WorkReportFlowProduction::query()
                ->where('work_report_id', $workReport->id)
                ->where('stage', $stage)
                ->where('production_id', '!=', $production->id)
                ->update(['is_current' => false]);

            return WorkReportFlowProduction::query()->updateOrCreate(
                [
                    'work_report_id' => $workReport->id,
                    'production_id' => $production->id,
                    'stage' => $stage,
                ],
                [
                    'is_current' => true,
                    'linked_at' => now(),
                    'linked_by' => auth()->id(),
                    'source' => $source,
                    'metadata' => array_filter([
                        'note_id' => $production->note_id,
                        'service_id' => $production->service_id,
                        'production_status' => $production->status,
                        'production_user_id' => $production->user_id,
                        ...$metadata,
                    ], fn ($value) => $value !== null),
                ]
            );
        });
    }

    public function resolveCurrentFinalWorkReport(int $noteId): ?WorkReport
    {
        return WorkReport::query()
            ->where('note_id', $noteId)
            ->where('canceled', false)
            ->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderByDesc('id')
            ->first();
    }
}
