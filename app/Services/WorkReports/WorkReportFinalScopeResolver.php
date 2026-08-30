<?php

namespace App\Services\WorkReports;

use App\Support\SicodeRules;
use Illuminate\Support\Collection;

class WorkReportFinalScopeResolver
{
    public const SCOPE_GENERAL = 'general';
    public const SCOPE_NETWORK = 'network';
    public const SCOPE_CONNECTION = 'connection';

    public const POLICY_REQUIRED = 'required';
    public const POLICY_SKIPPED_BY_SCOPE = 'skipped_by_scope';

    public function resolve(int|string|null $noteType, Collection $orders): array
    {
        if (!SicodeRules::workReportSplitsBtzeroEpFinalFlows() || (int) $noteType !== 1) {
            return [$this->scopePayload(self::SCOPE_GENERAL, $orders, 'legacy_general')];
        }

        $networkPrefixes = SicodeRules::workReportFinalScopeOrderPrefixes(self::SCOPE_NETWORK);
        $network = $this->matchingOrders($orders, $networkPrefixes);
        $connection = $this->ordersWithoutPrefixes($orders, $networkPrefixes);
        $payloads = [];

        if ($network->isNotEmpty()) {
            $payloads[] = $this->scopePayload(self::SCOPE_NETWORK, $network, 'order_prefix_match');
        }

        if ($connection->isNotEmpty()) {
            $payloads[] = $this->scopePayload(self::SCOPE_CONNECTION, $connection, 'non_network_order_prefix');
        }

        if (!empty($payloads)) {
            return $payloads;
        }

        return [$this->scopePayload(self::SCOPE_GENERAL, $orders, 'no_matching_btzero_ep_order_prefix')];
    }

    public function publicationRequired(string $scope): bool
    {
        return $scope !== self::SCOPE_CONNECTION;
    }

    public function publicationPolicy(string $scope): string
    {
        return $this->publicationRequired($scope)
            ? self::POLICY_REQUIRED
            : self::POLICY_SKIPPED_BY_SCOPE;
    }

    private function matchingOrders(Collection $orders, array $prefixes): Collection
    {
        if (empty($prefixes)) {
            return collect();
        }

        return $orders
            ->filter(function ($order) use ($prefixes) {
                $number = preg_replace('/\D+/', '', (string) ($order->order_number ?? $order->ordem ?? ''));

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($number, $prefix)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    private function ordersWithoutPrefixes(Collection $orders, array $prefixes): Collection
    {
        if (empty($prefixes)) {
            return $orders->values();
        }

        return $orders
            ->reject(function ($order) use ($prefixes) {
                $number = $this->normalizedOrderNumber($order);

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($number, $prefix)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    private function normalizedOrderNumber(object $order): string
    {
        return preg_replace('/\D+/', '', (string) ($order->order_number ?? $order->ordem ?? ''));
    }

    private function scopePayload(string $scope, Collection $orders, string $resolution): array
    {
        $first = $orders->first();

        return [
            'scope' => $scope,
            'resolution' => $resolution,
            'order_id' => $first->order_id ?? $first->id ?? null,
            'order_number' => $first->order_number ?? $first->ordem ?? null,
            'orders' => $orders
                ->map(fn ($order) => [
                    'id' => $order->order_id ?? $order->id ?? null,
                    'number' => $order->order_number ?? $order->ordem ?? null,
                ])
                ->values()
                ->all(),
        ];
    }
}
