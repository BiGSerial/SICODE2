<?php

namespace App\Services\Supervision;

use App\Models\{WorkReport, WorkReportFlowProduction};
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

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

    public function fastIdsSubquery(bool $excludeOpenProduction = true): QueryBuilder
    {
        return $this->fastBaseSubquery($excludeOpenProduction)->select('wr.id');
    }

    public function listSubquery(bool $excludeOpenProduction = true): QueryBuilder
    {
        return $this->fastBaseSubquery($excludeOpenProduction)
            ->select([
                'wr.id',
                'wr.note_id',
                'wr.created_at',
            ]);
    }

    public function noteIdsSubquery(bool $excludeOpenProduction = true): QueryBuilder
    {
        return DB::query()
            ->fromSub($this->listSubquery($excludeOpenProduction), 'candidate_work_reports')
            ->select('note_id')
            ->groupBy('note_id');
    }

    public function dispatchRowsSubquery(bool $includeAnyNonCanceledReport = false): QueryBuilder
    {
        $reports = $includeAnyNonCanceledReport
            ? DB::table("work_reports as wr")
                ->where("wr.canceled", false)
                ->selectRaw("wr.id as work_report_id, wr.note_id, COALESCE(wr.informed_at, wr.created_at) as row_created_at, NULL as passive_d5_id, NULL as partial_id")
            : $this->fastBaseSubquery(false)
                ->selectRaw("wr.id as work_report_id, wr.note_id, COALESCE(wr.informed_at, wr.created_at) as row_created_at, NULL as passive_d5_id, NULL as partial_id");

        $d5WorkReports = DB::table("five_notes as fn")
            ->join("work_reports as wr", function ($join) {
                $join->on("wr.id", "=", "fn.work_report_id")
                    ->on("wr.note_id", "=", "fn.note_id")
                    ->where("wr.canceled", false);
            })
            ->where("fn.is_completed", true)
            ->where("fn.is_supervisioned", false)
            ->where("fn.is_archived", false)
            ->selectRaw("wr.id as work_report_id, wr.note_id, COALESCE(wr.informed_at, wr.created_at) as row_created_at, NULL as passive_d5_id, NULL as partial_id")
            ->distinct();

        $partials = DB::table("partials as pt")
            ->where("pt.allow", true)
            ->where("pt.deny", false)
            ->where("pt.supervision", false)
            ->where("pt.payment", false)
            ->selectRaw("NULL as work_report_id, pt.note_id, pt.created_at as row_created_at, NULL as passive_d5_id, pt.id as partial_id");

        $passiveD5 = DB::table("five_notes as fn")
            ->whereNull("fn.work_report_id")
            ->where("fn.is_completed", true)
            ->where("fn.is_supervisioned", false)
            ->where("fn.is_archived", false)
            ->selectRaw("NULL as work_report_id, fn.note_id, fn.created_at as row_created_at, fn.id as passive_d5_id, NULL as partial_id");

        return $reports->union($d5WorkReports)->unionAll($partials)->unionAll($passiveD5);
    }

    private function fastBaseSubquery(bool $excludeOpenProduction = true): QueryBuilder
    {
        $query = DB::table('work_reports as wr')
            ->join('order_work_report as owr', 'owr.work_report_id', '=', 'wr.id')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->leftJoin('operations as op30_released', function ($join) {
                $join->on('op30_released.order_id', '=', 'o.id')
                    ->where('op30_released.operacao', '0030')
                    ->where(function ($query) {
                        $query->where('op30_released.status', 'like', 'CNPA%')
                            ->orWhere('op30_released.status', 'like', 'LIB%')
                            ->orWhere('op30_released.status', 'like', 'JBFI LIB%');
                    });
            })
            ->leftJoin('operations as op40_released', function ($join) {
                $join->on('op40_released.order_id', '=', 'o.id')
                    ->where('op40_released.operacao', '0040')
                    ->where(function ($query) {
                        $query->where('op40_released.status', 'like', 'LIB%')
                            ->orWhere('op40_released.status', 'like', 'JBFI LIB%');
                    });
            })
            ->where('wr.canceled', false)
            ->where('wr.rejected', false)
            ->where('o.statusSist', 'like', 'LIB%')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('cancellation_requests')
                    ->whereColumn('cancellation_requests.note_id', 'wr.note_id')
                    ->where('cancellation_requests.status', 'DONE')
                    ->where('cancellation_requests.scope', 'NOTE_FULL');
            })
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('op30_released.id')
                        ->whereNotNull('op40_released.id');
                });

                if (!SicodeRules::paymentIgnoresOperation40AfterOperation30Confirmed()) {
                    $query->orWhere(function ($query) {
                        $query->whereExists(function ($query) {
                            $query->selectRaw('1')
                                ->from('operations as op10_confirmed')
                                ->whereColumn('op10_confirmed.order_id', 'o.id')
                                ->where('op10_confirmed.operacao', '0010')
                                ->where('op10_confirmed.status', 'like', 'CONF%');
                        })
                            ->whereExists(function ($query) {
                                $query->selectRaw('1')
                                    ->from('operations as op30_confirmed')
                                    ->whereColumn('op30_confirmed.order_id', 'o.id')
                                    ->where('op30_confirmed.operacao', '0030')
                                    ->where('op30_confirmed.status', 'like', 'CONF%');
                            })
                            ->whereNotNull('op40_released.id');
                    });
                }
            })
            ->groupBy('wr.id', 'wr.note_id', 'wr.created_at', 'wr.informed_at');

        if ($excludeOpenProduction) {
            $query->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('work_report_flow_productions as wrfp')
                    ->join('productions as p', 'p.id', '=', 'wrfp.production_id')
                    ->whereColumn('wrfp.work_report_id', 'wr.id')
                    ->where('wrfp.stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                    ->where('wrfp.is_current', true)
                    ->where('p.completed', false)
                    ->where('p.confirmed', false);
            });
        }

        return $query;
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
