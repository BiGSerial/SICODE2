<?php

namespace App\Services\Wall\Screen;

use App\Models\SystemSetting;
use App\Models\WallScreen;
use App\Services\Wall\Contracts\WallScreenDataService;
use App\Services\Wall\Context\ScreenContext;
use App\Services\Wall\Fixed\AdsFixedDashboardDataService;
use App\Services\Wall\Fixed\ComplaintsFixedDashboardDataService;
use App\Services\Wall\Fixed\ProjectReviewFixedDashboardDataService;

class FixedChartScreenDataService implements WallScreenDataService
{
    public function __construct(
        private readonly AdsFixedDashboardDataService $ads,
        private readonly ProjectReviewFixedDashboardDataService $projectReview,
        private readonly ComplaintsFixedDashboardDataService $complaints,
    ) {
    }

    public function buildScreenPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id'                       => (int) $screen->id,
            'name'                     => (string) $screen->name,
            'screen_type'              => 'fixed_chart',
            'duration_seconds'         => (int) ($screen->duration_seconds ?: $this->defaultRotation()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'items'                    => [$this->buildItem($screen, $context->fixedChart)],
        ];
    }

    public function buildScreenManifestPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id'                       => (int) $screen->id,
            'name'                     => (string) $screen->name,
            'screen_type'              => 'fixed_chart',
            'duration_seconds'         => (int) ($screen->duration_seconds ?: $this->defaultRotation()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'loaded'                   => false,
            'items'                    => [$this->buildManifestItem($context->fixedChart)],
        ];
    }

    public function buildSingleItemPayload(WallScreen $screen, ScreenContext $context, string $serviceId): ?array
    {
        $item = $this->buildItem($screen, $context->fixedChart);

        return ((string) ($item['service_id'] ?? '') === $serviceId) ? $item : null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildItem(WallScreen $screen, string $fixedChart): array
    {
        return match ($fixedChart) {
            'ads_dashboard'            => $this->ads->buildItemPayload($screen),
            'project_review_dashboard' => $this->projectReview->buildItemPayload($screen),
            'complaints_dashboard'     => $this->complaints->buildItemPayload($screen),
            default                    => $this->placeholder($fixedChart),
        };
    }

    private function buildManifestItem(string $fixedChart): array
    {
        return match ($fixedChart) {
            'ads_dashboard'            => $this->ads->buildManifestItem(),
            'project_review_dashboard' => $this->projectReview->buildManifestItem(),
            'complaints_dashboard'     => $this->complaints->buildManifestItem(),
            default                    => $this->placeholder($fixedChart),
        };
    }

    public function placeholder(string $fixedChart): array
    {
        $label = match ($fixedChart) {
            'complaints_dashboard'     => 'RECLAMAÇÃO',
            'project_review_dashboard' => 'ANALISE DE PROJETO',
            default                    => 'FIXO',
        };

        return [
            'service_id'           => 'fixed-' . ($fixedChart ?: 'generic'),
            'service_name'         => $label . ' - Em Configuração',
            'previous_service_id'  => null,
            'previous_service_name' => null,
            'cards'                => ['queue_total' => 0, 'in_analysis' => 0, 'returned' => 0, 'previous_done' => 0, 'previous_ready' => 0],
            'ads_chart'            => ['kind' => 'dashboard', 'title' => $label, 'labels' => [], 'datasets' => []],
            'ads_dashboard'        => [
                'top_cards'    => [['label' => 'Status', 'value' => 'Em desenvolvimento']],
                'middle_cards' => [],
                'line_chart'   => ['labels' => [], 'datasets' => []],
                'bar_chart'    => ['labels' => [], 'datasets' => []],
                'queue_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0],
                'reuse_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0, 'reuse_rate' => 0],
            ],
            'project_review_dashboard' => [
                'top_cards'    => [['label' => 'Status', 'value' => 'Em desenvolvimento']],
                'middle_cards' => [],
                'line_chart'   => ['labels' => [], 'datasets' => []],
                'bar_chart'    => ['labels' => [], 'datasets' => []],
                'queue_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0],
                'reuse_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0, 'reuse_rate' => 0],
            ],
            'complaints_dashboard' => [
                'top_cards'    => [['label' => 'Status', 'value' => 'Em desenvolvimento']],
                'middle_cards' => [],
                'line_chart'   => ['labels' => [], 'datasets' => []],
                'bar_chart'    => ['labels' => [], 'datasets' => []],
                'queue_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0],
                'reuse_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0, 'reuse_rate' => 0],
            ],
            'queue_histogram'           => ['labels' => [], 'values' => []],
            'note_type_donut'           => ['labels' => ['Com produção', 'Sem produção'], 'values' => [0, 0], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily'          => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut'     => ['labels' => [], 'values' => []],
            'recent_completed'          => [],
            'week'                      => null,
        ];
    }

    private function defaultRotation(): int
    {
        return max(10, (int) (SystemSetting::getValue('wall_v2_rotation_seconds', '180') ?? '180'));
    }
}
