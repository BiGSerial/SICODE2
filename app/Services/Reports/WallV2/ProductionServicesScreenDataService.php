<?php

namespace App\Services\Reports\WallV2;

use App\Models\WallScreen;
use App\Services\Reports\WallV2\Contracts\WallScreenDataService;
use Closure;

class ProductionServicesScreenDataService implements WallScreenDataService
{
    public function __construct(
        private readonly Closure $rotationSeconds,
        private readonly Closure $buildItemPayload,
        private readonly Closure $resolveItemSourceConfig,
    ) {
    }

    public function buildScreenPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id' => (int) $screen->id,
            'name' => (string) $screen->name,
            'screen_type' => 'production_services',
            'duration_seconds' => (int) ($screen->duration_seconds ?: ($this->rotationSeconds)()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'items' => $screen->items
                ->filter(fn ($item) => $item->service)
                ->map(fn ($item) => ($this->buildItemPayload)(
                    $item->service,
                    $item->previousService,
                    (bool) $item->use_rule_builder,
                    ($this->resolveItemSourceConfig)($screen, $item)
                ))
                ->values()
                ->all(),
        ];
    }

    public function buildScreenManifestPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id' => (int) $screen->id,
            'name' => (string) $screen->name,
            'screen_type' => 'production_services',
            'duration_seconds' => (int) ($screen->duration_seconds ?: ($this->rotationSeconds)()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'loaded' => false,
            'items' => $screen->items
                ->filter(fn ($item) => $item->service)
                ->map(function ($item) {
                    return [
                        'service_id' => (string) $item->service->uuid,
                        'service_name' => (string) $item->service->service,
                        'previous_service_id' => $item->previousService ? (string) $item->previousService->uuid : null,
                        'previous_service_name' => $item->previousService ? (string) $item->previousService->service : null,
                        'ads_chart' => null,
                        'cards' => [],
                        'queue_histogram' => ['labels' => [], 'values' => []],
                        'note_type_donut' => ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
                        'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                        'production_daily' => ['labels' => [], 'assigned' => [], 'delivered' => []],
                        'internal_return_donut' => ['labels' => [], 'values' => []],
                        'recent_completed' => [],
                        'week' => null,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function buildSingleItemPayload(WallScreen $screen, ScreenContext $context, string $serviceId): ?array
    {
        $screenItem = $screen->items()
            ->where('enabled', true)
            ->where('service_id', $serviceId)
            ->with(['service', 'previousService'])
            ->first();

        if (!$screenItem || !$screenItem->service) {
            return null;
        }

        return ($this->buildItemPayload)(
            $screenItem->service,
            $screenItem->previousService,
            (bool) $screenItem->use_rule_builder,
            ($this->resolveItemSourceConfig)($screen, $screenItem)
        );
    }
}
