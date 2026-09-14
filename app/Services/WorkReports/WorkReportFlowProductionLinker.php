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

    public function linkD5FiscalizationToNetwork(Production $production, ?string $source = null, array $metadata = []): ?WorkReportFlowProduction
    {
        if (!(bool) $production->dfive) {
            return null;
        }

        $workReport = $this->resolveCurrentFinalWorkReport(
            (int) $production->note_id,
            WorkReportFlowProduction::SCOPE_NETWORK
        );

        if (!$workReport) {
            return null;
        }

        return DB::transaction(function () use ($production, $workReport, $source, $metadata): WorkReportFlowProduction {
            WorkReportFlowProduction::query()
                ->where('work_report_id', $workReport->id)
                ->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                ->where('final_scope', WorkReportFlowProduction::SCOPE_NETWORK)
                ->where('production_id', '!=', $production->id)
                ->update(['is_current' => false]);

            $link = WorkReportFlowProduction::query()->updateOrCreate(
                [
                    'work_report_id' => $workReport->id,
                    'production_id' => $production->id,
                    'stage' => WorkReportFlowProduction::STAGE_FISCALIZATION,
                    'final_scope' => WorkReportFlowProduction::SCOPE_NETWORK,
                ],
                [
                    'is_current' => true,
                    'linked_at' => now(),
                    'linked_by' => auth()->id(),
                    'source' => $source ?? 'dispatch_d5_fiscalization',
                    'metadata' => array_filter([
                        'note_id' => $production->note_id,
                        'service_id' => $production->service_id,
                        'production_status' => $production->status,
                        'production_user_id' => $production->user_id,
                        'd5_primary_scope' => WorkReportFlowProduction::SCOPE_NETWORK,
                        ...$metadata,
                    ], fn ($value) => $value !== null),
                ]
            );

            app(WorkReportCurrentStatusRefresher::class)->refresh($workReport->id);

            return $link;
        });
    }

    public function linkPayment(Production $production, ?string $source = null, array $metadata = [], string $finalScope = WorkReportFlowProduction::SCOPE_GENERAL): ?WorkReportFlowProduction
    {
        return $this->link($production, WorkReportFlowProduction::STAGE_PAYMENT, $source ?? 'dispatch_payment', $metadata, $finalScope);
    }

    public function linkPublicationForWorkReport(Production $production, WorkReport $workReport, ?string $source = null, array $metadata = [], string $finalScope = WorkReportFlowProduction::SCOPE_GENERAL): ?WorkReportFlowProduction
    {
        if ((bool) $production->partial || (bool) $production->dfive) {
            return null;
        }

        return DB::transaction(function () use ($production, $workReport, $source, $metadata, $finalScope): WorkReportFlowProduction {
            WorkReportFlowProduction::query()
                ->where('work_report_id', $workReport->id)
                ->where('stage', WorkReportFlowProduction::STAGE_PUBLICATION)
                ->where('final_scope', $finalScope)
                ->where('production_id', '!=', $production->id)
                ->update(['is_current' => false]);

            $link = WorkReportFlowProduction::query()->updateOrCreate(
                [
                    'work_report_id' => $workReport->id,
                    'production_id' => $production->id,
                    'stage' => WorkReportFlowProduction::STAGE_PUBLICATION,
                    'final_scope' => $finalScope,
                ],
                [
                    'is_current' => true,
                    'linked_at' => now(),
                    'linked_by' => auth()->id(),
                    'source' => $source ?? 'dispatch_publication',
                    'metadata' => array_filter([
                        'note_id' => $production->note_id,
                        'service_id' => $production->service_id,
                        'production_status' => $production->status,
                        'production_user_id' => $production->user_id,
                        ...$metadata,
                    ], fn ($value) => $value !== null),
                ]
            );

            app(WorkReportCurrentStatusRefresher::class)->refresh($workReport->id);

            return $link;
        });
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

    public function linkPaymentForWorkReport(Production $production, WorkReport|int $workReport, array $finalScopes, ?string $source = null, array $metadata = []): array
    {
        if ((bool) $production->partial || (bool) $production->dfive) {
            return [];
        }

        $workReport = $workReport instanceof WorkReport
            ? $workReport
            : WorkReport::query()->whereKey($workReport)->where('canceled', false)->first();

        if (!$workReport || (int) $workReport->note_id !== (int) $production->note_id) {
            return [];
        }

        $scopes = collect($finalScopes)
            ->map(fn ($scope) => (string) $scope)
            ->filter()
            ->unique()
            ->values();

        if ($scopes->isEmpty()) {
            $scopes = collect($workReport->selectedFinalScopesOrNull() ?? [])
                ->filter()
                ->unique()
                ->values();
        }

        if ($scopes->isEmpty()) {
            return [];
        }

        $links = DB::transaction(function () use ($production, $workReport, $source, $metadata, $scopes): array {
            $created = [];

            foreach ($scopes as $finalScope) {
                WorkReportFlowProduction::query()
                    ->where('work_report_id', $workReport->id)
                    ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
                    ->where('final_scope', $finalScope)
                    ->where('production_id', '!=', $production->id)
                    ->update(['is_current' => false]);

                $created[] = WorkReportFlowProduction::query()->updateOrCreate(
                    [
                        'work_report_id' => $workReport->id,
                        'production_id' => $production->id,
                        'stage' => WorkReportFlowProduction::STAGE_PAYMENT,
                        'final_scope' => $finalScope,
                    ],
                    [
                        'is_current' => true,
                        'linked_at' => now(),
                        'linked_by' => auth()->id(),
                        'source' => $source ?? 'dispatch_payment',
                        'metadata' => array_filter([
                            'note_id' => $production->note_id,
                            'service_id' => $production->service_id,
                            'production_status' => $production->status,
                            'production_user_id' => $production->user_id,
                            ...$metadata,
                        ], fn ($value) => $value !== null),
                    ]
                );
            }

            return $created;
        });

        app(WorkReportCurrentStatusRefresher::class)->refresh($workReport->id);

        return $links;
    }

    public function link(Production $production, string $stage, string $source, array $metadata = [], string $finalScope = WorkReportFlowProduction::SCOPE_GENERAL): ?WorkReportFlowProduction
    {
        if ((bool) $production->partial || (bool) $production->dfive) {
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
}
