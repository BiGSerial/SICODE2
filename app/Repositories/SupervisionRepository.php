<?php

namespace App\Repositories;

use App\Models\Note;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class SupervisionRepository
{
    /**
     * Retorna a consulta base para obter notas.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBaseQuery(): Builder
    {
        return $this->applyBaseRules(
            Note::query()
                ->excludeCanceledFullDone()
                ->leftjoin('work_reports', function ($join) {
                    $join->on('work_reports.note_id', '=', 'notes.id')
                        ->where('work_reports.canceled', false);
                })
        )
            ->select('notes.*', 'work_reports.created_at as work_dt_created')
            ->orderBy('work_dt_created', 'ASC');
    }

    /**
     * Cada linha do resultado corresponde a UM informe (work_reports.id do LEFT JOIN
     * em getBaseQuery, ja filtrado para nao considerar informe cancelado). D5 e Fluxo
     * Normal precisam ser avaliados contra o informe dessa linha especificamente, nao
     * contra "qualquer informe/ordem da nota" - senao um informe sem nada pendente
     * aparece na lista so por causa de outro informe da mesma nota. O fallback via
     * D5 sem work_report_id e para D5 legada (anterior ao vinculo com informe) -
     * vale independente de a nota ter ou nao um informe valido, pois essa D5 nunca
     * teve relacao com nenhum informe.
     */
    public function applyBaseRules(Builder $query): Builder
    {
        return $query->where(function ($q) {
                $q->orWhere(function ($q) {
                    $q->whereExists(function (QueryBuilder $sub) {
                        $sub->select(DB::raw(1))
                            ->from('five_notes')
                            ->whereColumn('five_notes.note_id', 'notes.id')
                            ->where('five_notes.is_supervisioned', false)
                            ->where('five_notes.is_completed', true)
                            ->where(function (QueryBuilder $scope) {
                                $scope->whereColumn('five_notes.work_report_id', 'work_reports.id')
                                    ->orWhereNull('five_notes.work_report_id');
                            });
                    });
                })
                ->orwhere(function ($sq) {
                    $sq->whereHas('Partials', function ($q2) {
                        $q2->where('supervision', false)
                            ->where('allow', true);
                    })
                    ->whereDoesntHave('WorkForm');
                })->orWhere(function ($sq) {
                    $sq->where('work_reports.rejected', false)
                        ->whereExists(function (QueryBuilder $sub) {
                            $sub->select(DB::raw(1))
                                ->from('order_work_report')
                                ->join('orders', 'orders.id', '=', 'order_work_report.order_id')
                                ->whereColumn('order_work_report.work_report_id', 'work_reports.id')
                                ->where('orders.statusSist', 'LIKE', 'LIB%')
                                ->where(function (QueryBuilder $orderScope) {
                                    $orderScope->where(function (QueryBuilder $branch) {
                                        $branch->whereExists(fn (QueryBuilder $op) => $this->operationExists($op, '0030', ['CNPA%', 'LIB%', 'JBFI LIB%']))
                                            ->whereExists(fn (QueryBuilder $op) => $this->operationExists($op, '0040', ['LIB%', 'JBFI LIB%']));
                                    })
                                    ->when(!SicodeRules::paymentIgnoresOperation40AfterOperation30Confirmed(), function (QueryBuilder $orWhere) {
                                        $orWhere->orWhere(function (QueryBuilder $branch) {
                                            $branch->whereExists(fn (QueryBuilder $op) => $this->operationExists($op, '0010', ['CONF%']))
                                                ->whereExists(fn (QueryBuilder $op) => $this->operationExists($op, '0030', ['CONF%']))
                                                ->whereExists(fn (QueryBuilder $op) => $this->operationExists($op, '0040', ['LIB%']));
                                        });
                                    });
                                });
                        });
                });
            });
    }

    private function operationExists(QueryBuilder $query, string $operacao, array $statusPatterns): QueryBuilder
    {
        return $query->select(DB::raw(1))
            ->from('operations')
            ->whereColumn('operations.order_id', 'orders.id')
            ->where('operations.operacao', $operacao)
            ->where(function (QueryBuilder $status) use ($statusPatterns) {
                foreach ($statusPatterns as $pattern) {
                    $status->orWhere('operations.status', 'like', $pattern);
                }
            });
    }
}
