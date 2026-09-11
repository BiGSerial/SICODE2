<?php

namespace App\Services\WorkReports;

use App\Models\{Production, WorkReport, WorkReportFlowProduction};
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

    public function linkFiscalizationForWorkReport(Production $production, WorkReport $workReport, ?string $source = null, array $metadata = [], ?string $finalScope = null): ?WorkReportFlowProduction
    {
        return $this->linkForWorkReport(
            $production,
            $workReport,
            WorkReportFlowProduction::STAGE_FISCALIZATION,
            $source ?? 'dispatch_fiscalization',
            $metadata,
            $finalScope
        );
    }

    public function linkPaymentForWorkReport(Production $production, WorkReport $workReport, ?string $source = null, array $metadata = [], ?string $finalScope = null): ?WorkReportFlowProduction
    {
        return $this->linkForWorkReport(
            $production,
            $workReport,
            WorkReportFlowProduction::STAGE_PAYMENT,
            $source ?? 'dispatch_payment',
            $metadata,
            $finalScope
        );
    }

    public function linkPublicationForWorkReport(Production $production, WorkReport $workReport, ?string $source = null, array $metadata = [], ?string $finalScope = null): ?WorkReportFlowProduction
    {
        $finalScope = $finalScope ?? $this->singleFinalScope($workReport);

        if (!$finalScope || !app(WorkReportFinalScopeResolver::class)->publicationRequired($finalScope)) {
            return null;
        }

        return $this->linkForWorkReport(
            $production,
            $workReport,
            WorkReportFlowProduction::STAGE_PUBLICATION,
            $source ?? 'dispatch_publication',
            $metadata,
            $finalScope
        );
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

        $workReport = $this->resolveCurrentFinalWorkReport((int) $production->note_id, $finalScope);

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

            $link = WorkReportFlowProduction::query()->updateOrCreate(
                [
                    'work_report_id' => $workReport->id,
                    'production_id'  => $production->id,
                    'stage'          => $stage,
                    'final_scope'    => $finalScope,
                ],
                [
                    'is_current' => true,
                    'linked_at'  => now(),
                    'linked_by'  => auth()->id(),
                    'source'     => $source,
                    'metadata'   => array_filter([
                        'note_id'            => $production->note_id,
                        'service_id'         => $production->service_id,
                        'production_status'  => $production->status,
                        'production_user_id' => $production->user_id,
                        ...$metadata,
                    ], fn ($value) => $value !== null),
                ]
            );

            app(WorkReportCurrentStatusRefresher::class)->refresh($workReport->id);

            return $link;
        });
    }

    public function linkForWorkReport(Production $production, WorkReport $workReport, string $stage, string $source, array $metadata = [], ?string $finalScope = null): ?WorkReportFlowProduction
    {
        if ((bool) $production->partial || (int) $production->note_id !== (int) $workReport->note_id) {
            return null;
        }

        $finalScope = $finalScope ?? $this->singleFinalScope($workReport);

        if (!$finalScope) {
            return null;
        }

        return DB::transaction(function () use ($production, $workReport, $stage, $source, $metadata, $finalScope): WorkReportFlowProduction {
            $workReport = WorkReport::query()
                ->whereKey($workReport->id)
                ->lockForUpdate()
                ->firstOrFail();

            WorkReportFlowProduction::query()
                ->where('work_report_id', $workReport->id)
                ->where('stage', $stage)
                ->where('final_scope', $finalScope)
                ->where('production_id', '!=', $production->id)
                ->update(['is_current' => false]);

            $link = WorkReportFlowProduction::query()->updateOrCreate(
                [
                    'work_report_id' => $workReport->id,
                    'production_id'  => $production->id,
                    'stage'          => $stage,
                    'final_scope'    => $finalScope,
                ],
                [
                    'is_current' => true,
                    'linked_at'  => now(),
                    'linked_by'  => auth()->id(),
                    'source'     => $source,
                    'metadata'   => array_filter([
                        'note_id'                 => $production->note_id,
                        'service_id'              => $production->service_id,
                        'production_status'       => $production->status,
                        'production_user_id'      => $production->user_id,
                        'explicit_work_report_id' => $workReport->id,
                        ...$metadata,
                    ], fn ($value) => $value !== null),
                ]
            );

            app(WorkReportCurrentStatusRefresher::class)->refresh($workReport->id);

            return $link;
        });
    }

    public function resolveCurrentFinalWorkReport(int $noteId, string $finalScope = WorkReportFlowProduction::SCOPE_GENERAL): ?WorkReport
    {
        return WorkReport::query()
            ->with(['Note', 'Orders'])
            ->where('note_id', $noteId)
            ->where('canceled', false)
            ->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderByDesc('id')
            ->get()
            ->first(function (WorkReport $workReport) use ($finalScope) {
                return collect($workReport->finalScopePayloads())
                    ->pluck('scope')
                    ->contains($finalScope);
            });
    }

    private function singleFinalScope(WorkReport $workReport): ?string
    {
        $workReport->loadMissing(['Note', 'Orders']);

        $selected = $workReport->selectedFinalScopesOrNull();

        if (is_array($selected) && count($selected) === 1) {
            return $selected[0];
        }

        $scopes = collect($workReport->finalScopePayloads())
            ->pluck('scope')
            ->unique()
            ->values();

        return $scopes->count() === 1 ? $scopes->first() : null;
    }
}
