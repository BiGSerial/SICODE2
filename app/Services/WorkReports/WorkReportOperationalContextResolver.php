<?php

namespace App\Services\WorkReports;

use App\Models\{Production, WorkReport};
use Illuminate\Support\Collection;

class WorkReportOperationalContextResolver
{
    public function forWorkReport(int|WorkReport $workReport, ?string $stage = null): WorkReportOperationalContext
    {
        $id = $workReport instanceof WorkReport ? (int) $workReport->id : (int) $workReport;

        return $this->forMany([$id], $stage)->get($id)
            ?? new WorkReportOperationalContext(
                status: WorkReportOperationalContext::STATUS_MISSING_RELATION,
                reason: 'work_report_not_found',
                stage: $stage,
            );
    }

    public function forMany(iterable $workReportIds, ?string $stage = null): Collection
    {
        $ids = collect($workReportIds)
            ->map(fn ($id) => (int) ($id instanceof WorkReport ? $id->id : $id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $reports = WorkReport::query()
            ->with([
                'Note',
                'Orders.Operations',
                'Adsform',
                'FlowProductions' => fn ($query) => $query
                    ->where('is_current', true)
                    ->when($stage, fn ($q) => $q->where('stage', $stage)),
                'FlowProductions.Production',
            ])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $ids->mapWithKeys(function (int $id) use ($reports, $stage) {
            $report = $reports->get($id);

            if (!$report) {
                return [$id => new WorkReportOperationalContext(
                    status: WorkReportOperationalContext::STATUS_MISSING_RELATION,
                    reason: 'work_report_not_found',
                    stage: $stage,
                )];
            }

            return [$id => $this->contextForLoadedWorkReport($report, $stage, WorkReportOperationalContext::STATUS_VALID)];
        });
    }

    public function forLegacyProduction(Production $production, string $stage, bool $publicationOnly = false): WorkReportOperationalContext
    {
        $production->loadMissing([
            'Note',
            'WorkReportFlowProductions' => fn ($query) => $query
                ->where('is_current', true)
                ->where('stage', $stage),
            'WorkReportFlowProductions.WorkReport.Note',
            'WorkReportFlowProductions.WorkReport.Orders.Operations',
        ]);

        $links = $production->WorkReportFlowProductions;

        if ($links->count() === 1) {
            return $this->contextForLoadedWorkReport(
                $links->first()->WorkReport,
                $stage,
                WorkReportOperationalContext::STATUS_VALID,
                $production
            );
        }

        if ($links->count() > 1) {
            return new WorkReportOperationalContext(
                status: WorkReportOperationalContext::STATUS_AMBIGUOUS,
                reason: 'production_has_multiple_current_work_report_links',
                note: $production->Note,
                production: $production,
                stage: $stage,
            );
        }

        if (!$production->Note) {
            return new WorkReportOperationalContext(
                status: WorkReportOperationalContext::STATUS_MISSING_RELATION,
                reason: 'production_without_note',
                production: $production,
                stage: $stage,
            );
        }

        $candidates = WorkReport::query()
            ->with(['Note', 'Orders.Operations', 'Adsform'])
            ->where('note_id', $production->note_id)
            ->where('canceled', false)
            ->get()
            ->filter(fn (WorkReport $workReport) => !$publicationOnly || $this->publicationApplies($workReport))
            ->values();

        if ($candidates->count() === 1) {
            return $this->contextForLoadedWorkReport(
                $candidates->first(),
                $stage,
                WorkReportOperationalContext::STATUS_LEGACY_RESOLVED,
                $production
            );
        }

        return new WorkReportOperationalContext(
            status: $candidates->isEmpty()
                ? WorkReportOperationalContext::STATUS_MISSING_RELATION
                : WorkReportOperationalContext::STATUS_AMBIGUOUS,
            reason: $candidates->isEmpty()
                ? 'no_active_work_report_for_legacy_production'
                : 'multiple_active_work_reports_for_legacy_production',
            note: $production->Note,
            production: $production,
            stage: $stage,
        );
    }

    private function contextForLoadedWorkReport(
        WorkReport $workReport,
        ?string $stage,
        string $status,
        ?Production $production = null
    ): WorkReportOperationalContext {
        return new WorkReportOperationalContext(
            status: $status,
            workReport: $workReport,
            note: $workReport->Note,
            production: $production,
            stage: $stage,
            finalScopes: collect($workReport->finalScopePayloads())->pluck('scope')->unique()->values()->all(),
            orders: $workReport->Orders,
        );
    }

    private function publicationApplies(WorkReport $workReport): bool
    {
        return collect($workReport->finalScopePayloads())
            ->pluck('scope')
            ->contains(fn (string $scope) => app(WorkReportFinalScopeResolver::class)->publicationRequired($scope));
    }
}
