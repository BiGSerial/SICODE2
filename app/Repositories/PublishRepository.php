<?php

namespace App\Repositories;

use App\Models\Note;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;

class PublishRepository
{
    /**
     * Retorna a consulta base para obter notas.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBaseQuery(bool $all_services = false, ?string $serviceUuid = null): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        if (!$all_services) {
            $query->whereHas('WorkForm', function (Builder $workReport) {
                $workReport
                    ->where('rejected', false)
                    ->whereHas('Orders', fn (Builder $order) => $this->publicationEligibleOrder($order));
            });
        }

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
