<?php

namespace App\Services\Supervision;

use App\Models\{WorkReport, WorkReportFlowProduction};
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;

class WorkReportSupervisionCandidateQuery
{
    public function query(): Builder
    {
        return $this->baseQuery()
            ->with([
                'Note',
                'Orders.Operations',
                'FlowProductions' => fn ($query) => $query
                    ->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                    ->where('is_current', true)
                    ->with('Production'),
            ])
            ->orderByRaw('COALESCE(informed_at, created_at) ASC')
            ->orderBy('id');
    }

    public function idsQuery(bool $excludeOpenProduction = true): Builder
    {
        return $this->baseQuery($excludeOpenProduction)->select('work_reports.id');
    }

    private function baseQuery(bool $excludeOpenProduction = true): Builder
    {
        return WorkReport::query()
            ->where('canceled', false)
            ->where('rejected', false)
            ->whereHas('Note', fn ($query) => $query->excludeCanceledFullDone())
            ->whereHas('Orders')
            ->whereHas('Orders', fn (Builder $query) => $this->applyEligibleOrderRules($query))
            ->when($excludeOpenProduction, function (Builder $query) {
                $query->whereDoesntHave('FlowProductions', function (Builder $query) {
                    $query->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                        ->where('is_current', true)
                        ->whereHas('Production', function (Builder $productionQuery) {
                            $productionQuery->where('completed', false)
                                ->where('confirmed', false);
                        });
                });
            });
    }

    private function applyEligibleOrderRules(Builder $query): void
    {
        $query
            ->where('statusSist', 'like', 'LIB%')
            ->where(function (Builder $query) {
                $query->where(fn (Builder $branch) => $this->whereHasReleasedOperation30And40($branch));

                if (!SicodeRules::paymentIgnoresOperation40AfterOperation30Confirmed()) {
                    $query->orWhere(fn (Builder $branch) => $this->whereHasConfirmedOperation10And30WithReleased40($branch));
                }
            });
    }

    private function whereHasReleasedOperation30And40(Builder $query): void
    {
        $query
            ->whereHas('Operations', function (Builder $query) {
                $query->where('operacao', '0030')
                    ->where(function (Builder $query) {
                        $query->where('status', 'like', 'CNPA%')
                            ->orWhere('status', 'like', 'LIB%')
                            ->orWhere('status', 'like', 'JBFI LIB%');
                    });
            })
            ->whereHas('Operations', function (Builder $query) {
                $query->where('operacao', '0040')
                    ->where(function (Builder $query) {
                        $query->where('status', 'like', 'LIB%')
                            ->orWhere('status', 'like', 'JBFI LIB%');
                    });
            });
    }

    private function whereHasConfirmedOperation10And30WithReleased40(Builder $query): void
    {
        $query
            ->whereHas('Operations', function (Builder $query) {
                $query->where('operacao', '0010')
                    ->where('status', 'like', 'CONF%');
            })
            ->whereHas('Operations', function (Builder $query) {
                $query->where('operacao', '0030')
                    ->where('status', 'like', 'CONF%');
            })
            ->whereHas('Operations', function (Builder $query) {
                $query->where('operacao', '0040')
                    ->where('status', 'like', 'LIB%');
            });
    }
}
