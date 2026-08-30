<?php

namespace App\Services\WorkReports;

use App\Models\Production;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use Illuminate\Support\Facades\DB;

class WorkReportFlowProductionLinker
{
    public function linkFiscalization(Production $production, ?string $source = null, array $metadata = [], string $finalScope = WorkReportFlowProduction::SCOPE_GENERAL): ?WorkReportFlowProduction
    {
        return $this->link($production, WorkReportFlowProduction::STAGE_FISCALIZATION, $source ?? 'dispatch_fiscalization', $metadata, $finalScope);
    }

    public function linkPayment(Production $production, ?string $source = null, array $metadata = [], string $finalScope = WorkReportFlowProduction::SCOPE_GENERAL): ?WorkReportFlowProduction
    {
        return $this->link($production, WorkReportFlowProduction::STAGE_PAYMENT, $source ?? 'dispatch_payment', $metadata, $finalScope);
    }

    public function linkPaymentForSingleAvailableScope(Production $production, ?string $source = null, array $metadata = []): ?WorkReportFlowProduction
    {
        $production->loadMissing('Note');
        $note = $production->Note;

        if (!$note) {
            return null;
        }

        $scopes = app(WorkReportFinalScopeOptions::class)->forNote($note);

        if (count($scopes) !== 1) {
            return null;
        }

        return $this->linkPayment($production, $source, $metadata, $scopes[0]['scope']);
    }

    public function linkPaymentForScopes(Production $production, array $finalScopes, ?string $source = null, array $metadata = []): array
    {
        return collect($finalScopes)
            ->map(fn (string $finalScope) => $this->linkPayment($production, $source, $metadata, $finalScope))
            ->filter()
            ->values()
            ->all();
    }

    public function link(Production $production, string $stage, string $source, array $metadata = [], string $finalScope = WorkReportFlowProduction::SCOPE_GENERAL): ?WorkReportFlowProduction
    {
        if ((bool) $production->partial) {
            return null;
        }

        $workReport = $this->resolveCurrentFinalWorkReport((int) $production->note_id);
        if (!$workReport) {
            return null;
        }

        return DB::transaction(function () use ($production, $workReport, $stage, $source, $metadata, $finalScope): WorkReportFlowProduction {
            WorkReportFlowProduction::query()
                ->where('work_report_id', $workReport->id)
                ->where('stage', $stage)
                ->where('final_scope', $finalScope)
                ->where('production_id', '!=', $production->id)
                ->update(['is_current' => false]);

            return WorkReportFlowProduction::query()->updateOrCreate(
                [
                    'work_report_id' => $workReport->id,
                    'production_id' => $production->id,
                    'stage' => $stage,
                    'final_scope' => $finalScope,
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
