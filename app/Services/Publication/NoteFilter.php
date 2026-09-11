<?php

namespace App\Services\Publication;

use App\Models\Note;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;

/** @package  */
class NoteFilter
{
    private $filters;
    private $btzeroform;

    public function filter($filterGroup, $btzeroform = true)
    {

        $this->btzeroform = $btzeroform;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$filterGroup])) {
            $this->filters = $_SESSION['filter'][$filterGroup];
        }

        $query = Note::query();

        $query->where(function ($q) {

            $q->where(function ($wq) {
                $wq->whereHas('WorkForm', function ($sq) {
                    $sq->where('rejected', false)
                        ->whereHas('Orders', fn (Builder $order) => $this->publicationEligibleOrder($order));
                });
            });
        });

        if (SicodeRules::workReportSplitsBtzeroEpFinalFlows()) {
            $networkPrefixes = SicodeRules::workReportFinalScopeOrderPrefixes('network');

            if (!empty($networkPrefixes)) {
                $query->where(function (Builder $scopeQuery) use ($networkPrefixes) {
                    $scopeQuery->where('type_note', '<>', 1)
                        ->orWhereNull('type_note')
                        ->orWhereHas('WorkForm.Orders', function (Builder $orderQuery) use ($networkPrefixes) {
                            $orderQuery->where(function (Builder $prefixQuery) use ($networkPrefixes) {
                                foreach ($networkPrefixes as $prefix) {
                                    $prefixQuery->orWhere('ordem', 'like', "{$prefix}%");
                                }
                            });
                        });
                });
            }
        }



        if (isset($this->filters['rubrica'])) {
            $query->where(function ($query) {
                $query->whereIn('rubrica', $this->filters['rubrica'])
                    ->orWhereNull('rubrica');
            });
        }

        if (isset($this->filters['city'])) {
            $query->where(function ($query) {
                $query->whereIn('lexp', $this->filters['city'])
                    ->orWhereNull('lexp');
            });
        }

        if (isset($this->filters['company'])) {
            $query->whereRelation('WorkForm', function ($q) {
                $q->whereIn('company_id', $this->filters['company']);
            });
        }






        return $query;
    }

    private function publicationEligibleOrder(Builder $query): Builder
    {
        return $query
            ->where('statusSist', 'LIKE', 'LIB%')
            ->whereHas('Operations', function (Builder $operation) {
                $operation->where('operacao', '0020')
                    ->where(function (Builder $status) {
                        $status->where('status', 'like', 'LIB%')
                            ->orWhere('status', 'like', 'CNPA%')
                            ->orWhere('status', 'like', 'JBFI LIB%');
                    });
            });
    }
}
