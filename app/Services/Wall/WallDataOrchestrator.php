<?php

namespace App\Services\Wall;

use App\Models\SystemSetting;
use App\Models\Wall;
use App\Models\WallScreen;
use App\Services\Wall\Context\ScreenContext;
use App\Services\Wall\Context\ScreenContextResolver;
use App\Services\Wall\Contracts\WallScreenDataService;
use App\Services\Wall\Screen\FixedChartScreenDataService;
use App\Services\Wall\Screen\ProductionScreenDataService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class WallDataOrchestrator
{
    public const KEY_ROTATION_SECONDS = 'wall_v2_rotation_seconds';
    public const KEY_REFRESH_SECONDS  = 'wall_v2_refresh_seconds';

    private ?int $rotationCache = null;
    private ?int $refreshCache  = null;

    public function __construct(
        private readonly ScreenContextResolver $contextResolver,
        private readonly ProductionScreenDataService $productionScreen,
        private readonly FixedChartScreenDataService $fixedScreen,
    ) {
    }

    // =========================================================================
    // Public API
    // =========================================================================

    public function getPayloadForWall(int $wallId, ?int $screenId = null): array
    {
        $wall    = Wall::query()->where('enabled', true)->findOrFail($wallId);
        $screens = $this->fetchScreens($wall, $screenId, is_null($screenId));

        return [
            'wall'             => ['id' => (int) $wall->id, 'name' => (string) $wall->name],
            'updated_at'       => now()->format('d/m/Y H:i:s'),
            'rotation_seconds' => $this->rotationSeconds(),
            'refresh_seconds'  => $this->refreshSeconds(),
            'screens'          => $screens->map(function (WallScreen $screen) {
                try {
                    return $this->buildScreenPayload($screen);
                } catch (Throwable $e) {
                    Log::error('wall build-screen-payload failed', [
                        'wall_id'   => (int) $screen->wall_id,
                        'screen_id' => (int) $screen->id,
                        'message'   => $e->getMessage(),
                    ]);
                    return $this->buildScreenErrorPayload($screen);
                }
            })->values()->all(),
        ];
    }

    public function getManifestForWall(int $wallId, ?int $screenId = null): array
    {
        $wall    = Wall::query()->where('enabled', true)->findOrFail($wallId);
        $screens = $this->fetchScreens($wall, $screenId, is_null($screenId));

        return [
            'wall'             => ['id' => (int) $wall->id, 'name' => (string) $wall->name],
            'updated_at'       => now()->format('d/m/Y H:i:s'),
            'rotation_seconds' => $this->rotationSeconds(),
            'refresh_seconds'  => $this->refreshSeconds(),
            'manifest'         => true,
            'screens'          => $screens->map(fn (WallScreen $screen) => $this->buildScreenManifestPayload($screen))->values()->all(),
        ];
    }

    public function getItemChartsPayload(int $wallId, int $screenId, string $serviceId, ?string $component = null): array
    {
        $screen = $this->fetchScreenForItem($wallId, $screenId);

        if (!$screen) {
            return ['screen_id' => $screenId, 'service_id' => $serviceId, 'updated_at' => now()->format('d/m/Y H:i:s'), 'component' => $component, 'charts' => []];
        }

        try {
            $item = $this->buildSingleItemPayload($screen, $serviceId);
        } catch (Throwable $e) {
            Log::error('wall build-item-payload failed', ['wall_id' => $wallId, 'screen_id' => $screenId, 'service_id' => $serviceId, 'message' => $e->getMessage()]);
            $item = null;
        }

        if (!$item) {
            return ['screen_id' => (int) $screen->id, 'service_id' => $serviceId, 'updated_at' => now()->format('d/m/Y H:i:s'), 'component' => $component, 'charts' => []];
        }

        if ($component) {
            return [
                'screen_id'  => (int) $screen->id,
                'service_id' => (string) ($item['service_id'] ?? $serviceId),
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component'  => $component,
                'data'       => $this->extractItemComponent($item, $component),
            ];
        }

        return [
            'screen_id'            => (int) $screen->id,
            'service_id'           => (string) ($item['service_id'] ?? $serviceId),
            'updated_at'           => now()->format('d/m/Y H:i:s'),
            'cards'                => $item['cards'] ?? [],
            'week'                 => $item['week'] ?? null,
            'previous_service_name' => $item['previous_service_name'] ?? null,
            'charts' => [
                'queue_histogram'           => $item['queue_histogram'] ?? ['labels' => [], 'values' => []],
                'note_type_donut'           => $item['note_type_donut'] ?? ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
                'production_open_histogram' => $item['production_open_histogram'] ?? ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                'production_daily'          => $item['production_daily'] ?? ['labels' => [], 'assigned' => [], 'delivered' => []],
                'internal_return_donut'     => $item['internal_return_donut'] ?? ['labels' => [], 'values' => []],
                'recent_completed'          => $item['recent_completed'] ?? [],
                'ads_dashboard'             => $item['ads_dashboard'] ?? null,
                'project_review_dashboard'  => $item['project_review_dashboard'] ?? null,
                'complaints_dashboard'      => $item['complaints_dashboard'] ?? null,
            ],
        ];
    }

    public function rotationSeconds(): int
    {
        if (!is_null($this->rotationCache)) return $this->rotationCache;
        return $this->rotationCache = max(10, (int) (SystemSetting::getValue(self::KEY_ROTATION_SECONDS, '180') ?? '180'));
    }

    public function refreshSeconds(): int
    {
        if (!is_null($this->refreshCache)) return $this->refreshCache;
        return $this->refreshCache = max(10, (int) (SystemSetting::getValue(self::KEY_REFRESH_SECONDS, '60') ?? '60'));
    }

    // =========================================================================
    // Private orchestration
    // =========================================================================

    private function buildScreenPayload(WallScreen $screen): array
    {
        $context = $this->contextResolver->resolve($screen);
        return $this->resolveScreenService($context)->buildScreenPayload($screen, $context);
    }

    private function buildScreenManifestPayload(WallScreen $screen): array
    {
        $context = $this->contextResolver->resolve($screen);
        return $this->resolveScreenService($context)->buildScreenManifestPayload($screen, $context);
    }

    private function buildSingleItemPayload(WallScreen $screen, string $serviceId): ?array
    {
        $context = $this->contextResolver->resolve($screen);
        return $this->resolveScreenService($context)->buildSingleItemPayload($screen, $context, $serviceId);
    }

    private function buildScreenErrorPayload(WallScreen $screen): array
    {
        $context = $this->contextResolver->resolve($screen);

        if ($context->isFixed()) {
            $item               = $this->fixedScreen->placeholder($context->fixedChart ?: 'generic');
            $item['service_name'] = trim(($item['service_name'] ?? 'FIXO') . ' (SEM DADOS)');
            $fixedKey           = $this->resolveFixedKey((string) ($item['service_id'] ?? ''));
            $item[$fixedKey]['top_cards'] = [['label' => 'Status', 'value' => 'SEM DADOS']];

            return [
                'id'                       => (int) $screen->id,
                'name'                     => (string) $screen->name,
                'screen_type'              => 'fixed_chart',
                'duration_seconds'         => (int) ($screen->duration_seconds ?: $this->rotationSeconds()),
                'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
                'items'                    => [$item],
            ];
        }

        return [
            'id'                       => (int) $screen->id,
            'name'                     => (string) $screen->name,
            'screen_type'              => 'production_services',
            'duration_seconds'         => (int) ($screen->duration_seconds ?: $this->rotationSeconds()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'items'                    => [],
        ];
    }

    private function resolveScreenService(ScreenContext $context): WallScreenDataService
    {
        return $context->isFixed() ? $this->fixedScreen : $this->productionScreen;
    }

    private function extractItemComponent(array $item, string $component): mixed
    {
        return match ($component) {
            'cards'                    => $item['cards'] ?? [],
            'week'                     => $item['week'] ?? null,
            'previous_service_name'    => $item['previous_service_name'] ?? null,
            'queue_histogram'          => $item['queue_histogram'] ?? ['labels' => [], 'values' => []],
            'note_type_donut'          => $item['note_type_donut'] ?? ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => $item['production_open_histogram'] ?? ['labels' => [], 'values' => []],
            'production_daily'         => $item['production_daily'] ?? ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut'    => $item['internal_return_donut'] ?? ['labels' => [], 'values' => []],
            'recent_completed'         => $item['recent_completed'] ?? [],
            'ads_dashboard'            => $item['ads_dashboard'] ?? null,
            'project_review_dashboard' => $item['project_review_dashboard'] ?? null,
            'complaints_dashboard'     => $item['complaints_dashboard'] ?? null,
            default                    => null,
        };
    }

    private function resolveFixedKey(string $serviceId): string
    {
        if (str_contains($serviceId, 'project_review')) return 'project_review_dashboard';
        if (str_contains($serviceId, 'complaints'))    return 'complaints_dashboard';
        return 'ads_dashboard';
    }

    private function fetchScreens(Wall $wall, ?int $screenId, bool $enabledOnly): Collection
    {
        return WallScreen::query()
            ->with(['items' => fn ($q) => $q->where('enabled', true)->with(['service', 'previousService'])->orderBy('display_order')->orderBy('id')])
            ->where('wall_id', $wall->id)
            ->when($screenId, fn ($q) => $q->whereKey($screenId))
            ->when($enabledOnly, fn ($q) => $q->where('enabled', true))
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    private function fetchScreenForItem(int $wallId, int $screenId): ?WallScreen
    {
        Wall::query()->where('enabled', true)->findOrFail($wallId);
        return WallScreen::query()->where('wall_id', $wallId)->whereKey($screenId)->first();
    }
}
