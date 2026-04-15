<?php

namespace App\Services\Reports\WallV2;

use App\Models\WallScreen;
use App\Services\Reports\WallV2\Contracts\WallScreenDataService;
use Closure;

class FixedChartScreenDataService implements WallScreenDataService
{
    public function __construct(
        private readonly Closure $rotationSeconds,
        private readonly Closure $buildAdsItemPayload,
        private readonly Closure $buildProjectReviewItemPayload,
        private readonly Closure $buildFixedPlaceholderItemPayload,
    ) {
    }

    public function buildScreenPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id' => (int) $screen->id,
            'name' => (string) $screen->name,
            'screen_type' => 'fixed_chart',
            'duration_seconds' => (int) ($screen->duration_seconds ?: ($this->rotationSeconds)()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'items' => [$this->buildFixedItem($screen, $context->fixedChart)],
        ];
    }

    public function buildScreenManifestPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id' => (int) $screen->id,
            'name' => (string) $screen->name,
            'screen_type' => 'fixed_chart',
            'duration_seconds' => (int) ($screen->duration_seconds ?: ($this->rotationSeconds)()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'loaded' => false,
            'items' => [$this->buildManifestItem($context->fixedChart)],
        ];
    }

    public function buildSingleItemPayload(WallScreen $screen, ScreenContext $context, string $serviceId): ?array
    {
        $item = $this->buildFixedItem($screen, $context->fixedChart);

        return ((string) ($item['service_id'] ?? '') === (string) $serviceId) ? $item : null;
    }

    private function buildFixedItem(WallScreen $screen, string $fixedChart): array
    {
        return match ($fixedChart) {
            'ads_dashboard' => ($this->buildAdsItemPayload)($screen),
            'project_review_dashboard' => ($this->buildProjectReviewItemPayload)($screen),
            default => ($this->buildFixedPlaceholderItemPayload)($fixedChart),
        };
    }

    private function buildManifestItem(string $fixedChart): array
    {
        return match ($fixedChart) {
            'ads_dashboard' => [
                'service_id' => 'ads-dashboard',
                'service_name' => 'ADS - Dashboard',
                'previous_service_id' => null,
                'previous_service_name' => null,
                'ads_chart' => ['kind' => 'dashboard'],
                'cards' => [],
                'queue_histogram' => ['labels' => [], 'values' => []],
                'note_type_donut' => ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
                'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                'production_daily' => ['labels' => [], 'assigned' => [], 'delivered' => []],
                'internal_return_donut' => ['labels' => [], 'values' => []],
                'recent_completed' => [],
                'week' => null,
            ],
            'project_review_dashboard' => [
                'service_id' => 'fixed-project_review_dashboard',
                'service_name' => 'ANALISE DE PROJETO',
                'previous_service_id' => null,
                'previous_service_name' => null,
                'ads_chart' => ['kind' => 'dashboard'],
                'cards' => [],
                'queue_histogram' => ['labels' => [], 'values' => []],
                'note_type_donut' => ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
                'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                'production_daily' => ['labels' => [], 'assigned' => [], 'delivered' => []],
                'internal_return_donut' => ['labels' => [], 'values' => []],
                'recent_completed' => [],
                'week' => null,
            ],
            default => ($this->buildFixedPlaceholderItemPayload)($fixedChart),
        };
    }
}
