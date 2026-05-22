<?php

namespace App\Services\Reports;

use App\Models\Production;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ProductionWallDataService
{
    public function getPayload(): array
    {
        $services = Service::query()
            ->select(['uuid', 'service'])
            ->where('status', true)
            ->orderBy('service')
            ->get();

        if ($services->isEmpty()) {
            $services = Service::query()
                ->select(['uuid', 'service'])
                ->orderBy('service')
                ->get();
        }

        return [
            'updated_at' => now()->format('d/m/Y H:i:s'),
            'slides' => $services
                ->map(fn (Service $service) => $this->buildServiceSlide($service))
                ->values()
                ->all(),
        ];
    }

    private function buildServiceSlide(Service $service): array
    {
        $serviceId = $service->uuid;

        $openQuery = Production::query()
            ->where('service_id', $serviceId)
            ->where('rejected', false)
            ->where('completed', false);

        $openTotal = (clone $openQuery)->count();

        $internalReturnOpen = (clone $openQuery)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('reclaims')
                    ->whereColumn('reclaims.production_id', 'productions.id')
                    ->where('reclaims.completed', false);
            })
            ->count();

        $normalOpen = max(0, $openTotal - $internalReturnOpen);

        $histogramBuckets = collect([
            ['label' => '0-2 dias', 'order' => 1],
            ['label' => '3-7 dias', 'order' => 2],
            ['label' => '8-15 dias', 'order' => 3],
            ['label' => '16-30 dias', 'order' => 4],
            ['label' => '31+ dias', 'order' => 5],
        ]);

        $histogramRows = (clone $openQuery)
            ->selectRaw("\n                CASE\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) <= 2 THEN '0-2 dias'\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) BETWEEN 3 AND 7 THEN '3-7 dias'\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) BETWEEN 8 AND 15 THEN '8-15 dias'\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) BETWEEN 16 AND 30 THEN '16-30 dias'\n                    ELSE '31+ dias'\n                END AS bucket_label,\n                CASE\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) <= 2 THEN 1\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) BETWEEN 3 AND 7 THEN 2\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) BETWEEN 8 AND 15 THEN 3\n                    WHEN DATEDIFF(NOW(), COALESCE(productions.dispatch_at, productions.created_at)) BETWEEN 16 AND 30 THEN 4\n                    ELSE 5\n                END AS bucket_order,\n                COUNT(*) AS total\n            ")
            ->groupBy('bucket_label', 'bucket_order')
            ->orderBy('bucket_order')
            ->get()
            ->keyBy('bucket_label');

        $histogramData = $histogramBuckets->map(function (array $bucket) use ($histogramRows) {
            $row = $histogramRows->get($bucket['label']);

            return [
                'label' => $bucket['label'],
                'total' => (int) ($row->total ?? 0),
            ];
        });

        $internalReturnPct = $openTotal > 0
            ? round(($internalReturnOpen / $openTotal) * 100, 1)
            : 0.0;

        return [
            'service_id' => $serviceId,
            'service_name' => (string) $service->service,
            'open_total' => $openTotal,
            'internal_return_open' => $internalReturnOpen,
            'normal_open' => $normalOpen,
            'internal_return_pct' => $internalReturnPct,
            'histogram' => [
                'labels' => $histogramData->pluck('label')->all(),
                'values' => $histogramData->pluck('total')->all(),
            ],
            'return_share' => [
                'labels' => ['Retorno interno', 'Demais producoes'],
                'values' => [$internalReturnOpen, $normalOpen],
            ],
        ];
    }
}
