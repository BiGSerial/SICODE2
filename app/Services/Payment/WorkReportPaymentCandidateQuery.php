<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Regra única de elegibilidade de um informe final para Medição/Pagamento. */
class WorkReportPaymentCandidateQuery
{
    public function whereEligibleOrder(Builder $query): Builder
    {
        $query->where('statusSist', 'like', 'LIB%')
            ->whereHas('Operations', fn (Builder $operation) => $this->whereOperationStatus($operation, '0030', ['CONF%']));

        if (SicodeRules::paymentIgnoresOperation40AfterOperation30Confirmed()) {
            return $query->whereHas('Operations', fn (Builder $operation) => $this->whereOperationStatus($operation, '0050', [
                'LIB%',
                'CNPA%',
                'JBFI LIB%',
            ]));
        }

        return $query
            ->whereHas('Operations', fn (Builder $operation) => $this->whereOperationStatus($operation, '0040', [
                'CNPA%',
                'JBFI LIB%',
                'LIB%',
            ]))
            ->whereHas('Operations', fn (Builder $operation) => $this->whereOperationStatus($operation, '0050', ['LIB%']));
    }

    public function orderIsEligible(Order $order): bool
    {
        if (!str_starts_with($this->normalizedStatus($order->statusSist), 'LIB')) {
            return false;
        }

        $statuses = function (string $operation) use ($order): Collection {
            return ($order->relationLoaded('Operations') ? $order->Operations : $order->Operations()->get())
                ->where('operacao', $operation)
                ->pluck('status')
                ->map(fn ($status) => $this->normalizedStatus($status));
        };

        if (!$statuses('0030')->contains(fn (string $status) => str_starts_with($status, 'CONF'))) {
            return false;
        }

        if (SicodeRules::paymentIgnoresOperation40AfterOperation30Confirmed()) {
            return $statuses('0050')->contains(
                fn (string $status) => str_starts_with($status, 'LIB')
                || str_starts_with($status, 'CNPA')
                || str_starts_with($status, 'JBFI LIB')
            );
        }

        return $statuses('0040')->contains(
            fn (string $status) => str_starts_with($status, 'CNPA')
            || str_starts_with($status, 'JBFI LIB')
            || str_starts_with($status, 'LIB')
        ) && $statuses('0050')->contains(fn (string $status) => str_starts_with($status, 'LIB'));
    }

    private function whereOperationStatus(Builder $query, string $operation, array $statuses): Builder
    {
        return $query->where('operacao', $operation)
            ->where(function (Builder $statusQuery) use ($statuses) {
                foreach ($statuses as $status) {
                    $statusQuery->orWhere('status', 'like', $status);
                }
            });
    }

    private function normalizedStatus(?string $status): string
    {
        return strtoupper(trim((string) $status));
    }
}
