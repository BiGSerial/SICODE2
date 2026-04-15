<?php

namespace App\Services\Reports;

use App\Custom\ProductionQueryBuilder;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\Wall;
use App\Models\WallScreen;
use App\Repositories\PublishRepository;
use App\Repositories\SupervisionRepository;
use App\Repositories\SurveyRepository;
use App\Services\Payment\NoteFilter as PaymentNoteFilter;
use App\Services\Publication\NoteFilter as PublicationNoteFilter;
use App\Services\Reports\WallV2\Contracts\WallScreenDataService;
use App\Services\Reports\WallV2\FixedChartScreenDataService;
use App\Services\Reports\WallV2\ProductionServicesScreenDataService;
use App\Services\Reports\WallV2\ScreenContext;
use App\Services\Reports\WallV2\ScreenContextResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductionWallV2DataService
{
    public const KEY_ROTATION_SECONDS = 'wall_v2_rotation_seconds';
    public const KEY_REFRESH_SECONDS = 'wall_v2_refresh_seconds';

    private ?int $rotationSecondsCache = null;
    private ?int $refreshSecondsCache = null;
    private ScreenContextResolver $screenContextResolver;
    private WallScreenDataService $productionScreenDataService;
    private WallScreenDataService $fixedScreenDataService;
    private PublicationNoteFilter $publicationNoteFilter;
    private PaymentNoteFilter $paymentNoteFilter;
    private PublishRepository $publishRepository;
    private SupervisionRepository $supervisionRepository;
    private SurveyRepository $surveyRepository;

    public function __construct(private AdsRequestedReportService $adsService)
    {
        $this->publicationNoteFilter = new PublicationNoteFilter();
        $this->paymentNoteFilter = new PaymentNoteFilter();
        $this->publishRepository = new PublishRepository();
        $this->supervisionRepository = new SupervisionRepository();
        $this->surveyRepository = new SurveyRepository();

        $this->screenContextResolver = new ScreenContextResolver();
        $this->productionScreenDataService = new ProductionServicesScreenDataService(
            rotationSeconds: fn () => $this->rotationSeconds(),
            buildItemPayload: fn (Service $service, ?Service $previousService, bool $useRuleBuilder, array $sourceConfig = []) => $this->buildItemPayload($service, $previousService, $useRuleBuilder, $sourceConfig),
            resolveItemSourceConfig: fn (WallScreen $screen, $item) => $this->resolveProductionItemSourceConfig($screen, $item),
        );
        $this->fixedScreenDataService = new FixedChartScreenDataService(
            rotationSeconds: fn () => $this->rotationSeconds(),
            buildAdsItemPayload: fn (WallScreen $screen) => $this->buildAdsItemPayload($screen),
            buildProjectReviewItemPayload: fn (WallScreen $screen) => $this->buildProjectReviewItemPayload($screen),
            buildFixedPlaceholderItemPayload: fn (string $fixedChart) => $this->buildFixedPlaceholderItemPayload($fixedChart),
        );
    }

    public function getPayloadForWall(int $wallId, ?int $screenId = null): array
    {
        $wall = Wall::query()->where('enabled', true)->findOrFail($wallId);

        $screens = WallScreen::query()
            ->with(['items' => function ($q) {
                $q->where('enabled', true)
                    ->with(['service', 'previousService'])
                    ->orderBy('display_order')
                    ->orderBy('id');
            }])
            ->where('enabled', true)
            ->where('wall_id', $wall->id)
            ->when($screenId, fn ($q) => $q->whereKey($screenId))
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return [
            'wall' => [
                'id' => (int) $wall->id,
                'name' => (string) $wall->name,
            ],
            'updated_at' => now()->format('d/m/Y H:i:s'),
            'rotation_seconds' => $this->rotationSeconds(),
            'refresh_seconds' => $this->refreshSeconds(),
            'screens' => $screens->map(function (WallScreen $screen) {
                try {
                    return $this->buildScreenPayload($screen);
                } catch (Throwable $e) {
                    Log::error('wall-v2 build screen payload failed', [
                        'wall_id' => (int) $screen->wall_id,
                        'screen_id' => (int) $screen->id,
                        'screen_name' => (string) $screen->name,
                        'screen_type' => (string) ($screen->screen_type ?: 'production_services'),
                        'message' => $e->getMessage(),
                    ]);

                    return $this->buildScreenErrorPayload($screen);
                }
            })->values()->all(),
        ];
    }

    public function getManifestForWall(int $wallId, ?int $screenId = null): array
    {
        $wall = Wall::query()->where('enabled', true)->findOrFail($wallId);

        $screens = WallScreen::query()
            ->with(['items' => function ($q) {
                $q->where('enabled', true)
                    ->with(['service', 'previousService'])
                    ->orderBy('display_order')
                    ->orderBy('id');
            }])
            ->where('enabled', true)
            ->where('wall_id', $wall->id)
            ->when($screenId, fn ($q) => $q->whereKey($screenId))
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return [
            'wall' => [
                'id' => (int) $wall->id,
                'name' => (string) $wall->name,
            ],
            'updated_at' => now()->format('d/m/Y H:i:s'),
            'rotation_seconds' => $this->rotationSeconds(),
            'refresh_seconds' => $this->refreshSeconds(),
            'manifest' => true,
            'screens' => $screens->map(fn (WallScreen $screen) => $this->buildScreenManifestPayload($screen))->values()->all(),
        ];
    }

    public function rotationSeconds(): int
    {
        if (!is_null($this->rotationSecondsCache)) {
            return $this->rotationSecondsCache;
        }

        $this->rotationSecondsCache = max(10, (int) (SystemSetting::getValue(self::KEY_ROTATION_SECONDS, '180') ?? '180'));

        return $this->rotationSecondsCache;
    }

    public function refreshSeconds(): int
    {
        if (!is_null($this->refreshSecondsCache)) {
            return $this->refreshSecondsCache;
        }

        $this->refreshSecondsCache = max(10, (int) (SystemSetting::getValue(self::KEY_REFRESH_SECONDS, '60') ?? '60'));

        return $this->refreshSecondsCache;
    }

    public function getItemChartsPayload(int $wallId, int $screenId, string $serviceId, ?string $component = null): array
    {
        $screen = $this->getScreenForItemPayload($wallId, $screenId);
        if (!$screen) {
            return [
                'screen_id' => $screenId,
                'service_id' => $serviceId,
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component' => $component,
                'charts' => [],
            ];
        }

        try {
            $item = $this->buildSingleItemPayload($screen, $serviceId);
        } catch (Throwable $e) {
            Log::error('wall-v2 build item payload failed', [
                'wall_id' => $wallId,
                'screen_id' => $screenId,
                'service_id' => $serviceId,
                'message' => $e->getMessage(),
            ]);

            $item = null;
        }

        if (!$item) {
            return [
                'screen_id' => (int) $screen->id,
                'service_id' => $serviceId,
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component' => $component,
                'charts' => [],
            ];
        }

        if ($component) {
            return [
                'screen_id' => (int) $screen->id,
                'service_id' => (string) ($item['service_id'] ?? $serviceId),
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component' => $component,
                'data' => $this->extractItemComponent($item, $component),
            ];
        }

        return [
            'screen_id' => (int) $screen->id,
            'service_id' => (string) ($item['service_id'] ?? $serviceId),
            'updated_at' => now()->format('d/m/Y H:i:s'),
            'cards' => $item['cards'] ?? [],
            'week' => $item['week'] ?? null,
            'previous_service_name' => $item['previous_service_name'] ?? null,
            'charts' => [
                'queue_histogram' => $item['queue_histogram'] ?? ['labels' => [], 'values' => []],
                'note_type_donut' => $item['note_type_donut'] ?? ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
                'production_open_histogram' => $item['production_open_histogram'] ?? ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                'production_daily' => $item['production_daily'] ?? ['labels' => [], 'assigned' => [], 'delivered' => []],
                'internal_return_donut' => $item['internal_return_donut'] ?? ['labels' => [], 'values' => []],
                'recent_completed' => $item['recent_completed'] ?? [],
                'ads_dashboard' => $item['ads_dashboard'] ?? null,
            ],
        ];
    }

    private function getScreenForItemPayload(int $wallId, int $screenId): ?WallScreen
    {
        Wall::query()->where('enabled', true)->findOrFail($wallId);

        return WallScreen::query()
            ->where('enabled', true)
            ->where('wall_id', $wallId)
            ->whereKey($screenId)
            ->first();
    }

    private function buildSingleItemPayload(WallScreen $screen, string $serviceId): ?array
    {
        $context = $this->screenContextResolver->resolve($screen);
        return $this->resolveScreenDataService($context)->buildSingleItemPayload($screen, $context, $serviceId);
    }

    private function extractItemComponent(array $item, string $component): mixed
    {
        return match ($component) {
            'cards' => $item['cards'] ?? [],
            'week' => $item['week'] ?? null,
            'previous_service_name' => $item['previous_service_name'] ?? null,
            'queue_histogram' => $item['queue_histogram'] ?? ['labels' => [], 'values' => []],
            'note_type_donut' => $item['note_type_donut'] ?? ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => $item['production_open_histogram'] ?? ['labels' => [], 'values' => []],
            'production_daily' => $item['production_daily'] ?? ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut' => $item['internal_return_donut'] ?? ['labels' => [], 'values' => []],
            'recent_completed' => $item['recent_completed'] ?? [],
            'ads_dashboard' => $item['ads_dashboard'] ?? null,
            default => null,
        };
    }

    private function buildScreenPayload(WallScreen $screen): array
    {
        $context = $this->screenContextResolver->resolve($screen);
        return $this->resolveScreenDataService($context)->buildScreenPayload($screen, $context);
    }

    private function buildScreenManifestPayload(WallScreen $screen): array
    {
        $context = $this->screenContextResolver->resolve($screen);
        return $this->resolveScreenDataService($context)->buildScreenManifestPayload($screen, $context);
    }

    private function buildFixedPlaceholderItemPayload(string $fixedChart): array
    {
        $label = match ($fixedChart) {
            'complaints_dashboard' => 'RECLAMAÇÃO',
            'project_review_dashboard' => 'ANALISE DE PROJETO',
            default => 'FIXO',
        };

        return [
            'service_id' => 'fixed-' . ($fixedChart ?: 'generic'),
            'service_name' => $label . ' - Em Configuração',
            'previous_service_id' => null,
            'previous_service_name' => null,
            'cards' => [
                'queue_total' => 0,
                'in_analysis' => 0,
                'returned' => 0,
                'previous_done' => 0,
                'previous_ready' => 0,
            ],
            'ads_chart' => [
                'kind' => 'dashboard',
                'title' => $label,
                'labels' => [],
                'datasets' => [],
            ],
            'ads_dashboard' => [
                'top_cards' => [
                    ['label' => 'Status', 'value' => 'Em desenvolvimento'],
                ],
                'middle_cards' => [],
                'line_chart' => ['labels' => [], 'datasets' => []],
                'bar_chart' => ['labels' => [], 'datasets' => []],
                'queue_donut' => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0],
                'reuse_donut' => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0, 'reuse_rate' => 0],
            ],
            'queue_histogram' => ['labels' => [], 'values' => []],
            'note_type_donut' => ['labels' => ['Com produção', 'Sem produção'], 'values' => [0, 0], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily' => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut' => ['labels' => [], 'values' => []],
            'recent_completed' => [],
            'week' => null,
        ];
    }

    private function buildScreenErrorPayload(WallScreen $screen): array
    {
        $context = $this->screenContextResolver->resolve($screen);
        $item = $this->buildFixedPlaceholderItemPayload($context->fixedChart ?: 'generic');
        $item['service_name'] = trim(($item['service_name'] ?? 'FIXO') . ' (SEM DADOS)');
        $item['ads_dashboard']['top_cards'] = [
            ['label' => 'Status', 'value' => 'SEM DADOS'],
        ];

        return [
            'id' => (int) $screen->id,
            'name' => (string) $screen->name,
            'screen_type' => $context->isFixed() ? 'fixed_chart' : 'production_services',
            'duration_seconds' => (int) ($screen->duration_seconds ?: $this->rotationSeconds()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'items' => [$item],
        ];
    }

    private function resolveScreenDataService(ScreenContext $context): WallScreenDataService
    {
        return $context->isFixed()
            ? $this->fixedScreenDataService
            : $this->productionScreenDataService;
    }

    private function buildAdsItemPayload(WallScreen $screen): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $today = $now->copy()->toDateString();
        $last15Start = $now->copy()->subDays(14)->toDateString();

        $monthFilters = [
            'date_in' => $monthStart,
            'date_out' => $today,
            'completed_in' => $monthStart,
            'completed_out' => $today,
            'status_exact' => null,
            'search' => null,
            'companyIds' => [],
            'statusFilter' => 'all',
            'chart_granularity' => null,
            'prefer_local_queue' => true,
        ];

        $chartFilters = $monthFilters;
        $chartFilters['date_in'] = $last15Start;
        $chartFilters['completed_in'] = $last15Start;
        $chartFilters['chart_granularity'] = 'day';

        $summary = $this->adsService->summarize($monthFilters);
        $flowMonth = $this->adsService->demandVsDeliverySeries($monthFilters);
        $flowChart = $this->adsService->demandVsDeliverySeries($chartFilters);
        $queue = $this->adsService->queueDonutSeries($monthFilters);
        $reuse = $this->adsService->reuseEconomyDonutSeries($monthFilters);

        $reuseLabels = [
            'Solicitações Reaproveitadas',
            'Novas Solicitações',
        ];
        $reuseValues = [
            (int) ($reuse['reused'] ?? 0),
            (int) ($reuse['queued'] ?? 0),
        ];
        $reuseColors = ['rgba(5,150,105,0.85)', 'rgba(59,130,246,0.8)'];

        $lineMeanOpen = (float) ($flowChart['analytics']['backlog_avg'] ?? 0);
        $lineMeanOverdue = (float) ($flowChart['analytics']['overdue_avg'] ?? 0);
        $labelCount = max(1, count($flowChart['labels'] ?? []));

        $lineChart = [
            'labels' => $flowChart['labels'] ?? [],
            'datasets' => [
                [
                    'label' => 'Acumulado em aberto',
                    'data' => $flowChart['open_backlog'] ?? [],
                    'borderColor' => '#7c3aed',
                    'backgroundColor' => 'rgba(124,58,237,.2)',
                    'pointBackgroundColor' => '#7c3aed',
                    'tension' => 0.25,
                    'fill' => false,
                ],
                [
                    'label' => 'Atrasadas (>24h)',
                    'data' => $flowChart['overdue_backlog'] ?? [],
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239,68,68,.2)',
                    'pointBackgroundColor' => '#ef4444',
                    'tension' => 0.25,
                    'fill' => false,
                ],
                [
                    'label' => 'Média (acumulado)',
                    'data' => array_fill(0, $labelCount, $lineMeanOpen),
                    'borderColor' => 'rgba(167,139,250,.9)',
                    'borderDash' => [6, 5],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
                [
                    'label' => 'Média (atrasadas)',
                    'data' => array_fill(0, $labelCount, $lineMeanOverdue),
                    'borderColor' => 'rgba(248,113,113,.9)',
                    'borderDash' => [6, 5],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
            ],
        ];

        $barChart = [
            'labels' => $flowChart['labels'] ?? [],
            'datasets' => [
                [
                    'label' => 'Demandas (solicitadas)',
                    'data' => $flowChart['requested'] ?? [],
                    'backgroundColor' => 'rgba(59,130,246,.8)',
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Saídas (concluídas)',
                    'data' => $flowChart['delivered'] ?? [],
                    'backgroundColor' => 'rgba(16,185,129,.8)',
                    'borderColor' => '#10b981',
                    'borderWidth' => 1,
                ],
            ],
        ];

        $queueDonut = [
            'labels' => $queue['labels'] ?? [],
            'values' => $queue['values'] ?? [],
            'colors' => $queue['colors'] ?? [],
            'total' => (int) ($queue['total'] ?? 0),
        ];

        $reuseDonut = [
            'labels' => $reuseLabels,
            'values' => $reuseValues,
            'colors' => $reuseColors,
            'total' => (int) ($reuse['total'] ?? 0),
            'reuse_rate' => (float) ($reuse['reuse_rate'] ?? 0),
        ];

        $kpis = [
            'queue_total' => (int) ($summary['opened_count'] ?? 0),
            'in_analysis' => (int) ($summary['in_progress_now_count'] ?? 0),
            'returned' => (int) ($queue['total'] ?? 0),
            'previous_done' => (int) ($flowMonth['analytics']['delivered_total'] ?? 0),
            'previous_ready' => (int) ($flowMonth['analytics']['requested_total'] ?? 0),
        ];

        return [
            'service_id' => 'ads-dashboard',
            'service_name' => 'ADS - Dashboard',
            'previous_service_id' => null,
            'previous_service_name' => null,
            'cards' => $kpis,
            'ads_chart' => [
                'kind' => 'dashboard',
                'title' => 'Dashboard ADS',
                'labels' => [],
                'datasets' => [],
            ],
            'ads_dashboard' => [
                'top_cards' => [
                    ['label' => 'Abertas no período', 'value' => (string) ((int) ($summary['opened_count'] ?? 0))],
                    ['label' => 'Média de aberturas/dia', 'value' => (string) number_format((float) ($summary['opened_daily_avg'] ?? 0), 2, ',', '.')],
                    ['label' => 'Média de entregas/dia', 'value' => (string) number_format((float) ($summary['delivered_daily_avg'] ?? 0), 2, ',', '.')],
                    ['label' => 'Tempo médio de entrega', 'value' => (string) ($summary['delivered_avg_label'] ?? '0')],
                    ['label' => 'Em execução agora', 'value' => (string) ((int) ($summary['in_progress_now_count'] ?? 0))],
                ],
                'middle_cards' => [
                    ['label' => 'Solicitadas', 'value' => (int) ($flowMonth['analytics']['requested_total'] ?? 0)],
                    ['label' => 'Concluídas', 'value' => (int) ($flowMonth['analytics']['delivered_total'] ?? 0)],
                    ['label' => 'Taxa de conclusão', 'value' => number_format((float) ($flowMonth['analytics']['completion_rate'] ?? 0), 1, ',', '.') . '%'],
                    ['label' => 'Abertas agora', 'value' => (int) ($flowMonth['analytics']['current_open'] ?? 0)],
                    ['label' => 'Atrasadas agora', 'value' => (int) ($flowMonth['analytics']['current_overdue'] ?? 0)],
                    ['label' => 'Média em aberto', 'value' => number_format((float) ($flowMonth['analytics']['backlog_avg'] ?? 0), 1, ',', '.')],
                    ['label' => 'Pico em aberto', 'value' => (int) ($flowMonth['analytics']['backlog_peak'] ?? 0)],
                    ['label' => 'Média atrasadas', 'value' => number_format((float) ($flowMonth['analytics']['overdue_avg'] ?? 0), 1, ',', '.')],
                ],
                'subtitle' => sprintf('Período vigente: %s a %s | Gráficos: últimos 15 dias', Carbon::parse($monthStart)->format('d/m'), Carbon::parse($today)->format('d/m')),
                'line_chart' => $lineChart,
                'bar_chart' => $barChart,
                'queue_donut' => $queueDonut,
                'reuse_donut' => $reuseDonut,
            ],
            'queue_histogram' => [
                'labels' => [],
                'values' => [],
            ],
            'note_type_donut' => [
                'labels' => ['Com produção', 'Sem produção'],
                'values' => [0, 0],
                'total' => 0,
                'associated' => 0,
            ],
            'production_open_histogram' => [
                'labels' => [],
                'values' => [],
                'normal_values' => [],
                'ri_values' => [],
            ],
            'production_daily' => [
                'labels' => [],
                'assigned' => [],
                'delivered' => [],
            ],
            'internal_return_donut' => [
                'labels' => [],
                'values' => [],
            ],
            'recent_completed' => [],
            'week' => null,
        ];
    }

    private function buildProjectReviewItemPayload(WallScreen $screen): array
    {
        [$periodStart, $periodEnd, $prevStart, $prevEnd] = $this->projectReviewComparisonWindows();

        $queueBase = $this->projectReviewQueueBaseQuery();
        $pendingBase = $this->projectReviewPendingQuery();
        $pendingTotal = (clone $queueBase)->count();
        $pendingWithCycle = (clone $pendingBase)->count();
        $pendingWithoutCycle = max(0, $pendingTotal - $pendingWithCycle);
        $pendingReturn = (clone $pendingBase)->whereRaw($this->projectReviewReturnExpr('productions.id') . ' = 1')->count();

        $pendingCycleCurrent = $this->projectReviewQueueEntriesCountInRange($periodStart, $periodEnd);
        $pendingCyclePrev = $this->projectReviewQueueEntriesCountInRange($prevStart, $prevEnd);
        $pendingReturnCycleCurrent = $this->projectReviewPendingCycleCountInRange($periodStart, $periodEnd, true);
        $pendingReturnCyclePrev = $this->projectReviewPendingCycleCountInRange($prevStart, $prevEnd, true);
        $pendingInitialTotal = max(0, $pendingWithCycle - $pendingReturn);

        $analyzedCurrent = $this->projectReviewAnalyzedCountInRange($periodStart, $periodEnd);
        $analyzedPrev = $this->projectReviewAnalyzedCountInRange($prevStart, $prevEnd);
        $avgDecisionHoursCurrent = $this->projectReviewAverageDecisionHoursInRange($periodStart, $periodEnd);
        $avgDecisionHoursPrev = $this->projectReviewAverageDecisionHoursInRange($prevStart, $prevEnd);

        $histogram = $this->projectReviewUnassociatedQueueHistogram();
        $costSummary = $this->projectReviewCostSummaryForWindow($periodStart, $periodEnd);
        $costSummaryPrev = $this->projectReviewCostSummaryForWindow($prevStart, $prevEnd);

        $lineLabels = $histogram['labels'];
        $lineValues = $histogram['values'];

        $plannedTotal = (float) ($costSummary['planned_total_cost'] ?? 0);
        $revisedTotal = (float) ($costSummary['revised_total_cost'] ?? 0);
        $plannedTotalPrev = (float) ($costSummaryPrev['planned_total_cost'] ?? 0);
        $revisedTotalPrev = (float) ($costSummaryPrev['revised_total_cost'] ?? 0);
        $increaseTotal = (float) ($costSummary['increase_total_cost'] ?? 0);
        $increaseTotalPrev = (float) ($costSummaryPrev['increase_total_cost'] ?? 0);
        $economyTotal = (float) ($costSummary['economy_total_cost'] ?? 0);
        $economyTotalPrev = (float) ($costSummaryPrev['economy_total_cost'] ?? 0);
        $maintainedOrders = (int) ($costSummary['maintained_orders_count'] ?? 0);
        $maintainedOrdersPrev = (int) ($costSummaryPrev['maintained_orders_count'] ?? 0);

        $netVariation = (float) ($costSummary['net_variation_cost'] ?? 0);
        $netVariationAbs = abs($netVariation);
        $netVariationPct = $plannedTotal > 0 ? ($netVariationAbs / $plannedTotal) * 100 : 0.0;
        $netIsIncrease = $netVariation >= 0;

        $companyPlanned = (float) ($costSummary['planned_company_total_cost'] ?? 0);
        $companyDelta = (float) ($costSummary['company_net_variation_cost'] ?? 0);
        $companyDeltaAbs = abs($companyDelta);
        $companyDeltaPct = $companyPlanned > 0 ? ($companyDeltaAbs / $companyPlanned) * 100 : 0.0;
        $companyIsIncrease = $companyDelta >= 0;

        $clientPlanned = (float) ($costSummary['planned_client_total_cost'] ?? 0);
        $clientDelta = (float) ($costSummary['client_net_variation_cost'] ?? 0);
        $clientDeltaAbs = abs($clientDelta);
        $clientDeltaPct = $clientPlanned > 0 ? ($clientDeltaAbs / $clientPlanned) * 100 : 0.0;
        $clientIsIncrease = $clientDelta >= 0;

        $pendingSplit = $this->projectReviewQueueSplit($pendingWithoutCycle, $pendingInitialTotal, $pendingReturn);
        $revisedCompany = (float) ($costSummary['revised_company_total_cost'] ?? 0);
        $revisedClient = (float) ($costSummary['revised_client_total_cost'] ?? 0);

        return [
            'service_id' => 'fixed-project_review_dashboard',
            'service_name' => 'ANALISE DE PROJETO',
            'previous_service_id' => null,
            'previous_service_name' => null,
            'cards' => [
                'queue_total' => (int) $pendingTotal,
                'queue_ov' => 0,
                'queue_notes' => 0,
                'returned' => (int) $pendingReturn,
                'previous_done' => (int) $analyzedCurrent,
                'next_entry' => 0,
            ],
            'ads_chart' => [
                'kind' => 'dashboard',
                'title' => 'ANALISE DE PROJETO',
                'labels' => [],
                'datasets' => [],
            ],
            'ads_dashboard' => [
                'subtitle' => sprintf(
                    'Período vigente %s a %s | Comparativo %s a %s | Gráficos: últimos 15 dias',
                    $periodStart->format('d/m'),
                    $periodEnd->format('d/m'),
                    $prevStart->format('d/m'),
                    $prevEnd->format('d/m')
                ),
                'line_chart_title' => 'Histograma da fila sem análise associada (dias na pilha)',
                'bar_chart_title' => 'Gráfico de barras removido',
                'queue_donut_title' => 'Composição da fila pendente',
                'reuse_donut_title' => 'Composição do valor revisado',
                'top_cards' => [
                    $this->buildProjectReviewKpiCard(
                        'Valor planejado',
                        $this->formatCurrencyBr($plannedTotal),
                        $plannedTotal,
                        $plannedTotalPrev,
                        'vs mês anterior',
                        false,
                        true
                    ),
                    $this->buildProjectReviewKpiCard(
                        'Valor revisado total',
                        $this->formatCurrencyBr($revisedTotal),
                        $revisedTotal,
                        $revisedTotalPrev,
                        'vs mês anterior',
                        false,
                        true
                    ),
                    $this->buildProjectReviewKpiCard(
                        'Somatório de acréscimos',
                        $this->formatCurrencyBr($increaseTotal),
                        $increaseTotal,
                        $increaseTotalPrev,
                        'vs mês anterior',
                        false,
                        true
                    ),
                    $this->buildProjectReviewKpiCard(
                        'Somatório de reduções',
                        $this->formatCurrencyBr($economyTotal),
                        $economyTotal,
                        $economyTotalPrev,
                        'vs mês anterior',
                        true,
                        true
                    ),
                    $this->buildProjectReviewKpiCard(
                        'Ordens sem alteração',
                        (string) $maintainedOrders,
                        $maintainedOrders,
                        $maintainedOrdersPrev,
                        'vs mês anterior'
                    ),
                ],
                'middle_cards' => [
                    [
                        'label' => 'Diferença líquida (acréscimos - reduções)',
                        'value' => $this->formatCurrencyBr($netVariationAbs),
                        'trend' => sprintf(
                            '%s %s (%s%%)',
                            $netIsIncrease ? '↑' : '↓',
                            $netIsIncrease ? 'Custo subiu' : 'Custo reduziu',
                            number_format($netVariationPct, 2, ',', '.')
                        ),
                        'trend_color' => $netIsIncrease ? '#ef4444' : '#22c55e',
                        'card_bg' => $netIsIncrease ? 'rgba(239,68,68,.10)' : 'rgba(34,197,94,.10)',
                        'card_border' => $netIsIncrease ? 'rgba(239,68,68,.35)' : 'rgba(34,197,94,.35)',
                    ],
                    [
                        'label' => 'Custo empresa (planejado x revisado)',
                        'value' => $this->formatCurrencyBr($companyDeltaAbs),
                        'trend' => sprintf(
                            '%s %s (%s%%)',
                            $companyIsIncrease ? '↑' : '↓',
                            $companyIsIncrease ? 'Custo subiu' : 'Custo reduziu',
                            number_format($companyDeltaPct, 2, ',', '.')
                        ),
                        'trend_color' => $companyIsIncrease ? '#ef4444' : '#22c55e',
                        'card_bg' => $companyIsIncrease ? 'rgba(239,68,68,.10)' : 'rgba(34,197,94,.10)',
                        'card_border' => $companyIsIncrease ? 'rgba(239,68,68,.35)' : 'rgba(34,197,94,.35)',
                    ],
                    [
                        'label' => 'Custo cliente (planejado x revisado)',
                        'value' => $this->formatCurrencyBr($clientDeltaAbs),
                        'trend' => sprintf(
                            '%s %s (%s%%)',
                            $clientIsIncrease ? '↑' : '↓',
                            $clientIsIncrease ? 'Custo subiu' : 'Custo reduziu',
                            number_format($clientDeltaPct, 2, ',', '.')
                        ),
                        'trend_color' => $clientIsIncrease ? '#ef4444' : '#22c55e',
                        'card_bg' => $clientIsIncrease ? 'rgba(239,68,68,.10)' : 'rgba(34,197,94,.10)',
                        'card_border' => $clientIsIncrease ? 'rgba(239,68,68,.35)' : 'rgba(34,197,94,.35)',
                    ],
                    ['label' => 'Fila atual', 'value' => (string) $pendingTotal],
                    $this->buildProjectReviewKpiCard('Sem análise associada', (string) $pendingWithoutCycle, $pendingWithoutCycle, 0, 'status 30 sem ciclo', false),
                    $this->buildProjectReviewKpiCard('Entradas no período', (string) $pendingCycleCurrent, $pendingCycleCurrent, $pendingCyclePrev, 'MTD'),
                    $this->buildProjectReviewKpiCard('Retorno na fila', (string) $pendingReturn, $pendingReturnCycleCurrent, $pendingReturnCyclePrev, 'MTD retornos', false),
                    $this->buildProjectReviewKpiCard('Decisões no período', (string) $analyzedCurrent, $analyzedCurrent, $analyzedPrev, 'MTD respostas'),
                    $this->buildProjectReviewKpiCard('Tempo médio envio > decisão', number_format($avgDecisionHoursCurrent, 1, ',', '.') . 'h', $avgDecisionHoursCurrent, $avgDecisionHoursPrev, 'MTD (horas)', false),
                ],
                'line_chart' => [
                    'labels' => $lineLabels,
                    'datasets' => [
                        [
                            'label' => 'Sem análise associada',
                            'data' => $lineValues,
                            'borderColor' => '#f59e0b',
                            'backgroundColor' => 'rgba(245,158,11,.22)',
                            'pointBackgroundColor' => '#f59e0b',
                            'tension' => 0.2,
                            'fill' => true,
                        ],
                    ],
                ],
                'bar_chart' => [
                    'labels' => [],
                    'datasets' => [],
                ],
                'queue_donut' => [
                    'labels' => $pendingSplit['labels'],
                    'values' => $pendingSplit['values'],
                    'colors' => $pendingSplit['colors'],
                    'total' => (int) $pendingTotal,
                ],
                'reuse_donut' => [
                    'labels' => ['Revisado empresa', 'Revisado cliente'],
                    'values' => [round($revisedCompany, 2), round($revisedClient, 2)],
                    'colors' => ['rgba(20,184,166,.82)', 'rgba(14,165,233,.82)'],
                    'total' => round($revisedCompany + $revisedClient, 2),
                    'reuse_rate' => 0,
                ],
            ],
            'queue_histogram' => ['labels' => [], 'values' => []],
            'note_type_donut' => ['labels' => ['Com produção', 'Sem produção'], 'values' => [0, 0], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily' => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut' => ['labels' => [], 'values' => []],
            'recent_completed' => [],
            'week' => [
                'start' => $periodStart->toDateString(),
                'end' => $periodEnd->toDateString(),
                'label' => sprintf('%s a %s', $periodStart->format('d/m'), $periodEnd->format('d/m')),
            ],
        ];
    }

    private function buildItemPayload(Service $service, ?Service $previousService, bool $useRuleBuilder, array $sourceConfig = []): array
    {
        [$start, $end] = $this->weeklyWindow();
        $dayLabels = $this->dailyDateLabels($start, $end);

        $queueAllQuery = $this->buildActivityQueueQuery($service, $useRuleBuilder, false, $sourceConfig);
        $activityNoteIdsQuery = (clone $queueAllQuery)->select('notes.id');
        $queueQuery = (clone $queueAllQuery)->where('notes.type_note', 2);
        $queueNotesQuery = (clone $queueAllQuery)->where(function ($q) {
            $q->whereNull('notes.type_note')
                ->orWhere('notes.type_note', '!=', 2);
        });

        $queueTotalAll = (clone $queueAllQuery)->count();
        $queueTotalOv = (clone $queueQuery)->count();
        $queueTotalNotes = max(0, $queueTotalAll - $queueTotalOv);
        $queueHistogram = $this->buildQueueAgeHistogram(
            $queueQuery,
            $service->uuid
        );
        $noteTypeDonut = $this->buildNoteTypeCoverageDonut($queueNotesQuery, $service->uuid);

        $productionOpenQuery = Production::query()
            ->where('service_id', $service->uuid)
            ->whereNotNull('att_at')
            ->whereHas('Note', function ($noteQuery) {
                $noteQuery->where(function ($rubricaQuery) {
                    $rubricaQuery->whereNull('rubrica')
                        ->orWhere('rubrica', '!=', 'Acompanhamento');
                });
            })
            ->whereRaw('NOT (status = ? AND completed = ?)', [5, 1]);

        $openAssigned = (clone $productionOpenQuery)->count();

        $internalOpen = (clone $productionOpenQuery)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('reclaims')
                    ->whereColumn('reclaims.production_id', 'productions.id')
                    ->where('reclaims.completed', false);
            })
            ->count();

        $productionOpenRiQuery = (clone $productionOpenQuery)
            ->where('productions.d5', true);

        $productionOpenNormalQuery = (clone $productionOpenQuery)
            ->where(function ($q) {
                $q->whereNull('productions.d5')
                    ->orWhere('productions.d5', false);
            });

        $openHistogramNormal = $this->buildAgeHistogram(
            $productionOpenNormalQuery,
            'productions.att_at'
        );
        $openHistogramRi = $this->buildAgeHistogram(
            $productionOpenRiQuery,
            'productions.att_at'
        );
        $openHistogram = [
            'labels' => $openHistogramNormal['labels'],
            'normal_values' => $openHistogramNormal['values'],
            'ri_values' => $openHistogramRi['values'],
            'values' => array_map(
                fn ($i) => (int) ($openHistogramNormal['values'][$i] ?? 0) + (int) ($openHistogramRi['values'][$i] ?? 0),
                array_keys($openHistogramNormal['labels'])
            ),
        ];

        $productionDaily = $this->buildProductionDailyFlow($service->uuid, $dayLabels, $start, $end);
        $internalTypes = $this->buildInternalReturnTypesDonut($service->uuid);

        $previousDone = 0;
        $nextEntry = 0;

        if ($previousService) {
            $previousDoneQuery = Production::query()
                ->where('service_id', $previousService->uuid)
                ->where('rejected', false)
                ->where('completed', true)
                ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

            $previousDone = (clone $previousDoneQuery)->count();

            $nextEntry = (clone $previousDoneQuery)
                ->whereIn('productions.note_id', $activityNoteIdsQuery)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('analises')
                        ->whereColumn('analises.production_id', 'productions.id')
                        ->whereIn('analises.conclusion', [
                            'ENVIADO AO DESENHO/ORÇAMENTO',
                            'ENVIADO AO DESENHO',
                            'ENVIADO PARA ORÇAMENTO',
                        ]);
                })
                ->count();
        }

        $recentCompleted = Production::query()
            ->with(['Note:id,note', 'User:id,name', 'Company:id,name', 'Reclaim:id,production_id'])
            ->where('service_id', $service->uuid)
            ->where('rejected', false)
            ->where('completed', true)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderByDesc('completed_at')
            ->limit(12)
            ->get()
            ->map(function (Production $production) {
                return [
                    'note' => (string) ($production->Note?->note ?? '-'),
                    'user_name' => (string) ($production->User?->name ?? '-'),
                    'company_name' => (string) ($production->Company?->name ?? '-'),
                    'type' => $production->Reclaim ? 'RI' : 'Normal',
                    'completed_at' => optional($production->completed_at)->format('d/m/Y H:i') ?? '-',
                ];
            })
            ->values()
            ->all();

        return [
            'service_id' => (string) $service->uuid,
            'service_name' => (string) $service->service,
            'previous_service_id' => $previousService?->uuid,
            'previous_service_name' => $previousService?->service,
            'week' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'label' => sprintf('%s a %s', $start->format('d/m'), $end->format('d/m')),
            ],
            'cards' => [
                'queue_total' => (int) $queueTotalAll,
                'queue_ov' => (int) $queueTotalOv,
                'queue_notes' => (int) $queueTotalNotes,
                'returned' => (int) $internalOpen,
                'previous_done' => (int) $previousDone,
                'next_entry' => (int) $nextEntry,
                'queue_ov_only' => true,
            ],
            'queue_histogram' => $queueHistogram,
            'note_type_donut' => $noteTypeDonut,
            'production_open_histogram' => $openHistogram,
            'production_daily' => $productionDaily,
            'internal_return_donut' => $internalTypes,
            'recent_completed' => $recentCompleted,
            'production_histogram' => [
                'labels' => $openHistogram['labels'],
                'datasets' => [[
                    'label' => 'Atribuido aberto',
                    'backgroundColor' => 'rgba(0, 206, 201, .65)',
                    'borderColor' => '#00cec9',
                    'data' => $openHistogram['values'],
                ]],
            ],
        ];
    }

    private function buildActivityQueueQuery(Service $service, bool $useRuleBuilder, bool $notAssignedOnly = false, array $sourceConfig = []): Builder
    {
        $source = (string) ($sourceConfig['source'] ?? 'rule_builder');
        $query = $this->buildQueryBySource($source, $service, $useRuleBuilder, $sourceConfig);

        if ($notAssignedOnly) {
            $query->where(function ($q) use ($service) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) use ($service) {
                        $subquery->where('service_id', $service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }

        return $query;
    }

    private function buildQueryBySource(string $source, Service $service, bool $useRuleBuilder, array $sourceConfig): Builder
    {
        return match ($source) {
            'publication_note_filter' => $this->publicationNoteFilter->filter(
                (string) ($sourceConfig['filter_group'] ?? 'publication'),
                (bool) ($sourceConfig['btzeroform'] ?? true)
            ),
            'payment_note_filter' => $this->paymentNoteFilter->filter(
                $sourceConfig['search'] ?? null,
                (string) ($sourceConfig['filter_group'] ?? 'payment')
            ),
            'publish_repository' => $this->publishRepository->getBaseQuery(
                (bool) ($sourceConfig['all_services'] ?? false)
            ),
            'supervision_repository' => $this->supervisionRepository->getBaseQuery(),
            'survey_repository' => $this->surveyRepository->getBaseQuery(),
            default => $this->buildLegacyProductionQuery($service, $useRuleBuilder),
        };
    }

    private function buildLegacyProductionQuery(Service $service, bool $useRuleBuilder): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        if ($useRuleBuilder) {
            $service->loadMissing('Status');
            ProductionQueryBuilder::applyRules($query, $service->Status);
        } else {
            $query->where('nstats', $service->status);
        }

        return $query;
    }

    private function resolveProductionItemSourceConfig(WallScreen $screen, mixed $item): array
    {
        $screenConfig = (array) ($screen->screen_config ?? []);
        $defaultSource = (string) ($screenConfig['production_source'] ?? 'rule_builder');
        $map = (array) ($screenConfig['production_sources'] ?? []);
        $serviceId = (string) ($item->service_id ?? '');
        $rawItemConfig = $serviceId !== '' ? ($map[$serviceId] ?? []) : [];

        $itemConfig = is_string($rawItemConfig)
            ? ['source' => $rawItemConfig]
            : (array) $rawItemConfig;

        if (!isset($itemConfig['source']) || trim((string) $itemConfig['source']) === '') {
            $itemConfig['source'] = $defaultSource;
        }

        return $itemConfig;
    }

    private function weeklyWindow(): array
    {
        $end = now()->endOfDay();
        $start = now()->subDays(6)->startOfDay();

        return [$start, $end];
    }

    private function dailyDateLabels(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $labels;
    }

    private function buildDailyHistogram(Builder $baseQuery, string $sqlDateExpr, array $dayLabels): array
    {
        $rows = (clone $baseQuery)
            ->selectRaw($sqlDateExpr . ' as day_key, COUNT(*) as total')
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get()
            ->pluck('total', 'day_key');

        return [
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('d/m'), $dayLabels),
            'values' => array_map(fn ($d) => (int) ($rows[$d] ?? 0), $dayLabels),
        ];
    }

    private function buildAgeHistogram(Builder $baseQuery, string $sqlDateExpr, int $maxDays = 30): array
    {
        $labels = [];
        for ($day = 0; $day < $maxDays; $day++) {
            $labels[] = (string) $day;
        }
        $labels[] = $maxDays . '+';

        $buckets = array_fill_keys($labels, 0);
        $maxLabel = $maxDays . '+';
        $ageDaysExpr = 'GREATEST(0, DATEDIFF(CURDATE(), DATE(' . $sqlDateExpr . ')))';
        $bucketExpr = "CASE WHEN {$ageDaysExpr} >= {$maxDays} THEN '{$maxLabel}' ELSE CAST({$ageDaysExpr} AS CHAR) END";

        $rows = (clone $baseQuery)
            ->whereNotNull($sqlDateExpr)
            ->selectRaw($bucketExpr . ' as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->get();

        foreach ($rows as $row) {
            $bucket = (string) ($row->bucket ?? '');
            if (!array_key_exists($bucket, $buckets)) {
                continue;
            }

            $buckets[$bucket] = (int) ($row->total ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => array_map(fn ($label) => (int) ($buckets[$label] ?? 0), $labels),
        ];
    }

    private function buildQueueAgeHistogram(Builder $baseQuery, string $serviceId, int $maxDays = 30): array
    {
        $labels = [];
        for ($day = 0; $day < $maxDays; $day++) {
            $labels[] = (string) $day;
        }
        $labels[] = $maxDays . '+';

        $totals = array_fill_keys($labels, 0);
        $assigned = array_fill_keys($labels, 0);
        $maxLabel = $maxDays . '+';

        $statusDateExpr = "
            COALESCE(
                NULLIF(CAST(notes.dt_status AS DATETIME), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%Y-%m-%dT%H:%i:%s.%fZ'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%Y-%m-%dT%H:%i:%sZ'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%Y-%m-%dT%H:%i:%s.%f'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%Y-%m-%dT%H:%i:%s'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%Y-%m-%d %H:%i:%s'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%Y-%m-%d'), '0000-00-00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%Y%m%d'), '0000-00-00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%d/%m/%Y %H:%i:%s'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE(notes.dt_status, '%d/%m/%Y'), '0000-00-00')
            )
        ";

        $assignedProductions = Production::query()
            ->selectRaw('note_id, MIN(att_at) as first_att_at')
            ->where('service_id', $serviceId)
            ->where('rejected', false)
            ->whereNotNull('att_at')
            ->groupBy('note_id');

        $statusReferenceDateExpr = "DATE({$statusDateExpr})";
        $safeReferenceDateExpr = "CASE WHEN {$statusReferenceDateExpr} > CURDATE() THEN CURDATE() ELSE {$statusReferenceDateExpr} END";
        $ageDaysExpr = "GREATEST(0, DATEDIFF(CURDATE(), {$safeReferenceDateExpr}))";

        $queueRows = DB::query()
            ->fromSub((clone $baseQuery)->select('notes.id', 'notes.dt_status'), 'notes')
            ->leftJoinSub($assignedProductions, 'pa', function ($join) {
                $join->on('pa.note_id', '=', 'notes.id');
            })
            ->whereRaw($statusDateExpr . ' IS NOT NULL')
            ->selectRaw('notes.id as id')
            ->selectRaw($ageDaysExpr . ' as age_days')
            ->selectRaw('CASE WHEN pa.note_id IS NULL THEN 0 ELSE 1 END as has_assigned');

        $bucketExpr = "CASE WHEN q.age_days >= {$maxDays} THEN '{$maxLabel}' ELSE CAST(q.age_days AS CHAR) END";

        $rows = DB::query()
            ->fromSub($queueRows, 'q')
            ->selectRaw($bucketExpr . ' as bucket')
            ->selectRaw('q.has_assigned')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket', 'q.has_assigned')
            ->get();

        foreach ($rows as $row) {
            $bucket = (string) $row->bucket;
            if (array_key_exists($bucket, $totals)) {
                $qty = (int) $row->total;
                $totals[$bucket] += $qty;
                if ((int) $row->has_assigned === 1) {
                    $assigned[$bucket] += $qty;
                }
            }
        }

        $totalValues = array_values($totals);
        $assignedValues = array_values($assigned);
        $withoutAssignedValues = array_map(fn($t, $a) => max(0, $t - $a), $totalValues, $assignedValues);

        return [
            'labels' => $labels,
            'values' => $totalValues,
            'assigned_values' => $assignedValues,
            'without_assigned_values' => $withoutAssignedValues,
        ];
    }

    private function buildNoteTypeCoverageDonut(Builder $queueNotesQuery, string $serviceId): array
    {
        $rows = DB::query()
            ->fromSub((clone $queueNotesQuery)->select('notes.id'), 'n')
            ->leftJoinSub(
                Production::query()
                    ->select('note_id')
                    ->where('service_id', $serviceId)
                    ->where('rejected', false)
                    ->whereNotNull('att_at')
                    ->groupBy('note_id'),
                'pa',
                function ($join) {
                    $join->on('pa.note_id', '=', 'n.id');
                }
            )
            ->selectRaw('CASE WHEN pa.note_id IS NULL THEN 0 ELSE 1 END as has_associated')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('has_associated')
            ->get();

        $associated = 0;
        $withoutAssociated = 0;

        foreach ($rows as $row) {
            $totalBucket = (int) ($row->total ?? 0);
            if ((int) ($row->has_associated ?? 0) === 1) {
                $associated += $totalBucket;
                continue;
            }

            $withoutAssociated += $totalBucket;
        }

        $total = $associated + $withoutAssociated;

        return [
            'labels' => ['Com produção associada', 'Sem produção associada'],
            'values' => [(int) $associated, (int) $withoutAssociated],
            'total' => (int) $total,
            'associated' => (int) $associated,
            'relation' => $total > 0
                ? [round(($associated / $total) * 100, 1), round(($withoutAssociated / $total) * 100, 1)]
                : [0, 0],
        ];
    }

    private function buildProductionDailyFlow(string $serviceId, array $dayLabels, Carbon $start, Carbon $end): array
    {
        $assigned = Production::query()
            ->where('service_id', $serviceId)
            ->where('rejected', false)
            ->whereHas('Note', function ($noteQuery) {
                $noteQuery->where(function ($rubricaQuery) {
                    $rubricaQuery->whereNull('rubrica')
                        ->orWhere('rubrica', '!=', 'Acompanhamento');
                });
            })
            ->whereNotNull('att_at')
            ->whereBetween('att_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(att_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get()
            ->pluck('total', 'day_key');

        $delivered = Production::query()
            ->where('service_id', $serviceId)
            ->where('rejected', false)
            ->where('completed', true)
            ->whereHas('Note', function ($noteQuery) {
                $noteQuery->where(function ($rubricaQuery) {
                    $rubricaQuery->whereNull('rubrica')
                        ->orWhere('rubrica', '!=', 'Acompanhamento');
                });
            })
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(completed_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get()
            ->pluck('total', 'day_key');

        return [
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('d/m'), $dayLabels),
            'assigned' => array_map(fn ($d) => (int) ($assigned[$d] ?? 0), $dayLabels),
            'delivered' => array_map(fn ($d) => (int) ($delivered[$d] ?? 0), $dayLabels),
        ];
    }

    private function buildInternalReturnTypesDonut(string $serviceId): array
    {
        $base = Reclaim::query()
            ->where('service_id', $serviceId)
            ->where('completed', false);

        $counts = [
            'Viabilities' => (clone $base)->whereHas('Viabilities')->count(),
            'Waiting' => (clone $base)->whereHas('Waiting')->count(),
            'Approvals' => (clone $base)->whereHas('Approvals')->count(),
            'Externals' => (clone $base)->whereHas('Externals')->count(),
        ];

        $labels = array_keys($counts);
        $values = array_map(fn ($label) => (int) ($counts[$label] ?? 0), $labels);
        $total = (int) array_sum($values);
        $relation = array_map(
            fn ($v) => $total > 0 ? round((((int) $v) / $total) * 100, 1) : 0.0,
            $values
        );

        return [
            'labels' => $labels,
            'values' => $values,
            'relation' => $relation,
            'total' => (int) $total,
        ];
    }

    private function projectReviewPendingQuery(): Builder
    {
        return Production::query()
            ->where('status', Production::STATUS_IN_PROJECT_REVIEW)
            ->whereHas('ProjectReviewCycles');
    }

    private function projectReviewQueueBaseQuery(): Builder
    {
        return Production::query()
            ->where('status', Production::STATUS_IN_PROJECT_REVIEW);
    }

    private function projectReviewReturnExpr(string $productionIdColumn): string
    {
        $rejectedStatus = (int) Production::STATUS_REJECTED_PROJECT_REVIEW;

        return "
            CASE
                WHEN (
                    COALESCE((
                        SELECT MAX(c.round_number)
                        FROM project_review_cycles c
                        WHERE c.production_id = {$productionIdColumn}
                    ), 1) > 1
                    OR EXISTS (
                        SELECT 1
                        FROM project_review_cycles c2
                        WHERE c2.production_id = {$productionIdColumn}
                          AND c2.decision = 'REJECTED'
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM notetimelines nt
                        WHERE nt.production_id = {$productionIdColumn}
                          AND nt.status = {$rejectedStatus}
                    )
                ) THEN 1
                ELSE 0
            END
        ";
    }

    private function projectReviewComparisonWindows(): array
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfDay();

        $daysSpan = max(1, $periodStart->copy()->startOfDay()->diffInDays($periodEnd->copy()->startOfDay()) + 1);

        $prevStart = $periodStart->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEndCandidate = $prevStart->copy()->addDays($daysSpan - 1)->endOfDay();
        $prevMonthEnd = $prevStart->copy()->endOfMonth();
        $prevEnd = $prevEndCandidate->gt($prevMonthEnd) ? $prevMonthEnd : $prevEndCandidate;

        return [$periodStart, $periodEnd, $prevStart, $prevEnd];
    }

    private function projectReviewQueueEntriesCountInRange(Carbon $start, Carbon $end): int
    {
        $submittedCycles = (int) DB::table('project_review_cycles as cy')
            ->whereNotNull('cy.submitted_at')
            ->whereBetween('cy.submitted_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count('cy.id');

        $unassociatedEntries = (int) DB::table('notetimelines as nt')
            ->join('productions as p', 'p.id', '=', 'nt.production_id')
            ->leftJoin('project_review_cycles as cy', 'cy.production_id', '=', 'p.id')
            ->whereNull('cy.id')
            ->where('nt.status', Production::STATUS_IN_PROJECT_REVIEW)
            ->whereBetween('nt.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->distinct()
            ->count('nt.production_id');

        return $submittedCycles + $unassociatedEntries;
    }

    private function projectReviewPendingCycleCountInRange(Carbon $start, Carbon $end, bool $onlyReturn = false): int
    {
        $query = DB::table('project_review_cycles as cy')
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->where('p.status', Production::STATUS_IN_PROJECT_REVIEW)
            ->where('cy.decision', 'PENDING')
            ->whereNotNull('cy.submitted_at')
            ->whereBetween('cy.submitted_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        if ($onlyReturn) {
            $query->whereRaw($this->projectReviewReturnExpr('p.id') . ' = 1');
        }

        return (int) $query->count();
    }

    private function projectReviewAnalyzedCountInRange(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('project_review_cycles')
            ->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->whereNotNull('decided_at')
            ->whereBetween('decided_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count();
    }

    private function projectReviewAverageDaily(float|int $count, Carbon $start, Carbon $end): float
    {
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        return round(((float) $count) / $days, 2);
    }

    private function projectReviewDeadlineHistogram(Builder $pendingBase, int $maxDays = 30): array
    {
        $labels = [];
        for ($day = 0; $day < $maxDays; $day++) {
            $labels[] = (string) $day;
        }
        $labels[] = $maxDays . '+';
        $maxLabel = $maxDays . '+';

        $initial = array_fill_keys($labels, 0);
        $returns = array_fill_keys($labels, 0);

        $returnExpr = $this->projectReviewReturnExpr('p.id');
        $elapsedExpr = 'GREATEST(0, COALESCE(30 - n.days_left, 0))';
        $bucketExpr = "CASE WHEN {$elapsedExpr} >= {$maxDays} THEN '{$maxLabel}' ELSE CAST({$elapsedExpr} AS CHAR) END";

        $rows = DB::query()
            ->fromSub((clone $pendingBase)->select('productions.id', 'productions.note_id'), 'p')
            ->leftJoin('notes as n', 'n.id', '=', 'p.note_id')
            ->whereNotNull('n.id')
            ->selectRaw($bucketExpr . ' as bucket')
            ->selectRaw($returnExpr . ' as is_return')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket', 'is_return')
            ->get();

        foreach ($rows as $row) {
            $bucket = (string) ($row->bucket ?? '');
            if (!array_key_exists($bucket, $initial)) {
                continue;
            }

            $qty = (int) ($row->total ?? 0);
            if ((int) ($row->is_return ?? 0) === 1) {
                $returns[$bucket] += $qty;
            } else {
                $initial[$bucket] += $qty;
            }
        }

        $initialValues = array_values($initial);
        $returnValues = array_values($returns);

        return [
            'labels' => $labels,
            'initial_values' => $initialValues,
            'return_values' => $returnValues,
            'values' => array_map(
                fn ($a, $b) => (int) $a + (int) $b,
                $initialValues,
                $returnValues
            ),
        ];
    }

    private function projectReviewDailyAnalysisSeries(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $analyzedRows = DB::table('project_review_cycles')
            ->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->whereNotNull('decided_at')
            ->whereBetween('decided_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(decided_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->pluck('total', 'day_key');

        $submittedRows = DB::table('project_review_cycles')
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(submitted_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->pluck('total', 'day_key');

        return [
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('d/m'), $labels),
            'analyzed' => array_map(fn ($d) => (int) ($analyzedRows[$d] ?? 0), $labels),
            'submitted' => array_map(fn ($d) => (int) ($submittedRows[$d] ?? 0), $labels),
        ];
    }

    private function projectReviewUnassociatedQueueHistogram(): array
    {
        $entrySub = DB::table('notetimelines')
            ->selectRaw('production_id, MAX(created_at) as entered_at')
            ->where('status', Production::STATUS_IN_PROJECT_REVIEW)
            ->groupBy('production_id');

        $rows = DB::table('productions as p')
            ->leftJoin('project_review_cycles as cy', 'cy.production_id', '=', 'p.id')
            ->leftJoinSub($entrySub, 'entry_nt', function ($join) {
                $join->on('entry_nt.production_id', '=', 'p.id');
            })
            ->where('p.status', Production::STATUS_IN_PROJECT_REVIEW)
            ->whereNull('cy.id')
            ->selectRaw('GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(entry_nt.entered_at, p.updated_at, p.created_at)))) as age_days')
            ->get();

        $labels = ['0d', '1d', '2d', '3d', '4d', '5-7d', '8-15d', '16d+'];
        $buckets = array_fill_keys($labels, 0);

        foreach ($rows as $row) {
            $days = (int) ($row->age_days ?? 0);
            if ($days <= 4) {
                $bucket = "{$days}d";
            } elseif ($days <= 7) {
                $bucket = '5-7d';
            } elseif ($days <= 15) {
                $bucket = '8-15d';
            } else {
                $bucket = '16d+';
            }

            $buckets[$bucket] = (int) ($buckets[$bucket] ?? 0) + 1;
        }

        return [
            'labels' => array_keys($buckets),
            'values' => array_values($buckets),
        ];
    }

    private function projectReviewAverageDecisionHoursInRange(Carbon $start, Carbon $end): float
    {
        $avgHours = (float) DB::table('project_review_cycles')
            ->whereNotNull('submitted_at')
            ->whereNotNull('decided_at')
            ->whereBetween('decided_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, decided_at)) as avg_hours')
            ->value('avg_hours');

        return round($avgHours, 1);
    }

    private function projectReviewQueueSplit(int $pendingWithoutCycle, int $pendingInitial, int $pendingReturn): array
    {
        return [
            'labels' => ['Sem análise associada', 'Com análise (inicial)', 'Com análise (retorno)'],
            'values' => [max(0, $pendingWithoutCycle), max(0, $pendingInitial), max(0, $pendingReturn)],
            'colors' => ['rgba(148,163,184,.9)', 'rgba(59,130,246,.85)', 'rgba(245,158,11,.9)'],
        ];
    }

    private function projectReviewCostSummaryForWindow(Carbon $start, Carbon $end): array
    {
        $productionIds = Production::query()
            ->whereHas('ProjectReviewCycles', function ($q) use ($start, $end) {
                $q->whereDate('submitted_at', '>=', $start->toDateString())
                    ->whereDate('submitted_at', '<=', $end->toDateString());
            })
            ->pluck('id');

        return $this->projectReviewCostVariationSummary($productionIds);
    }

    private function projectReviewCostVariationSummary(Collection $productionIds): array
    {
        if ($productionIds->isEmpty()) {
            return [
                'planned_total_cost' => 0,
                'revised_total_cost' => 0,
                'planned_company_total_cost' => 0,
                'planned_client_total_cost' => 0,
                'revised_company_total_cost' => 0,
                'revised_client_total_cost' => 0,
                'company_net_variation_cost' => 0,
                'client_net_variation_cost' => 0,
                'economy_total_cost' => 0,
                'increase_total_cost' => 0,
                'net_variation_cost' => 0,
                'maintained_orders_count' => 0,
            ];
        }

        $rows = DB::table('project_review_orders as o')
            ->join('project_review_cycles as cy', 'cy.id', '=', 'o.cycle_id')
            ->whereIn('cy.production_id', $productionIds)
            ->selectRaw('cy.production_id, cy.round_number, o.id as order_id, o.order_number, o.total_cost, o.company_cost, o.client_cost')
            ->orderBy('cy.production_id')
            ->orderBy('cy.round_number')
            ->orderBy('o.id')
            ->get();

        $planned = 0.0;
        $revised = 0.0;
        $plannedCompany = 0.0;
        $plannedClient = 0.0;
        $revisedCompany = 0.0;
        $revisedClient = 0.0;
        $economy = 0.0;
        $increase = 0.0;
        $maintainedCount = 0;

        $rows->groupBy('production_id')->each(function ($productionRows) use (&$planned, &$revised, &$plannedCompany, &$plannedClient, &$revisedCompany, &$revisedClient, &$economy, &$increase, &$maintainedCount) {
            $byRound = collect($productionRows)->groupBy('round_number');
            if ($byRound->isEmpty()) {
                return;
            }

            $prefixSeries = $this->projectReviewPrefixSeriesByRound($byRound);
            $hasPrefixData = false;

            foreach (['170', '190', '150', '200'] as $prefix) {
                $series = collect($prefixSeries[$prefix] ?? [])->values();
                if ($series->isEmpty()) {
                    continue;
                }

                $hasPrefixData = true;
                $first = (array) $series->first();
                $last = (array) $series->last();

                $planned += (float) ($first['total'] ?? 0);
                $revised += (float) ($last['total'] ?? 0);
                $plannedCompany += (float) ($first['company'] ?? 0);
                $plannedClient += (float) ($first['client'] ?? 0);
                $revisedCompany += (float) ($last['company'] ?? 0);
                $revisedClient += (float) ($last['client'] ?? 0);

                if ($series->count() >= 2) {
                    $delta = round(((float) ($last['total'] ?? 0)) - ((float) ($first['total'] ?? 0)), 2);
                    if ($delta > 0) {
                        $increase += $delta;
                    } elseif ($delta < 0) {
                        $economy += abs($delta);
                    } else {
                        $maintainedCount++;
                    }
                }
            }

            if (!$hasPrefixData) {
                $roundTotals = $byRound
                    ->sortKeys()
                    ->map(function ($roundRows) {
                        $rows = collect($roundRows);
                        return [
                            'total' => (float) $rows->sum('total_cost'),
                            'company' => (float) $rows->sum('company_cost'),
                            'client' => (float) $rows->sum('client_cost'),
                        ];
                    })
                    ->values();

                $firstTotals = (array) $roundTotals->first();
                $lastTotals = (array) $roundTotals->last();
                $planned += (float) ($firstTotals['total'] ?? 0);
                $revised += (float) ($lastTotals['total'] ?? 0);
                $plannedCompany += (float) ($firstTotals['company'] ?? 0);
                $plannedClient += (float) ($firstTotals['client'] ?? 0);
                $revisedCompany += (float) ($lastTotals['company'] ?? 0);
                $revisedClient += (float) ($lastTotals['client'] ?? 0);

                if ($roundTotals->count() >= 2) {
                    $delta = round(((float) ($lastTotals['total'] ?? 0)) - ((float) ($firstTotals['total'] ?? 0)), 2);
                    if ($delta > 0) {
                        $increase += $delta;
                    } elseif ($delta < 0) {
                        $economy += abs($delta);
                    } else {
                        $maintainedCount++;
                    }
                }
            }
        });

        return [
            'planned_total_cost' => $planned,
            'revised_total_cost' => $revised,
            'planned_company_total_cost' => $plannedCompany,
            'planned_client_total_cost' => $plannedClient,
            'revised_company_total_cost' => $revisedCompany,
            'revised_client_total_cost' => $revisedClient,
            'company_net_variation_cost' => $revisedCompany - $plannedCompany,
            'client_net_variation_cost' => $revisedClient - $plannedClient,
            'economy_total_cost' => $economy,
            'increase_total_cost' => $increase,
            'net_variation_cost' => $revised - $planned,
            'maintained_orders_count' => $maintainedCount,
        ];
    }

    private function projectReviewPrefixSeriesByRound(Collection $byRound): array
    {
        $series = collect(['170', '190', '150', '200'])
            ->mapWithKeys(fn ($prefix) => [$prefix => []])
            ->all();

        $byRound->sortKeys()->each(function ($roundRows) use (&$series) {
            $latestByPrefix = [];

            foreach (collect($roundRows) as $row) {
                $prefix = $this->extractOrderPrefixFromNumber((string) ($row->order_number ?? ''));
                if (is_null($prefix)) {
                    continue;
                }

                $latestByPrefix[$prefix] = [
                    'total' => (float) ($row->total_cost ?? 0),
                    'company' => (float) ($row->company_cost ?? 0),
                    'client' => (float) ($row->client_cost ?? 0),
                ];
            }

            foreach (['170', '190', '150', '200'] as $prefix) {
                if (array_key_exists($prefix, $latestByPrefix)) {
                    $series[$prefix][] = $latestByPrefix[$prefix];
                }
            }
        });

        return $series;
    }

    private function extractOrderPrefixFromNumber(string $orderNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $orderNumber);
        if (!$digits || strlen($digits) < 3) {
            return null;
        }

        $prefix = substr($digits, 0, 3);
        return in_array($prefix, ['170', '190', '150', '200'], true) ? $prefix : null;
    }

    private function buildProjectReviewKpiCard(
        string $label,
        string $value,
        float|int $current,
        float|int $previous,
        string $suffix = '',
        bool $increaseIsPositive = true,
        bool $isCurrency = false
    ): array
    {
        $delta = (float) $current - (float) $previous;
        $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'same');
        $prefix = $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '→');
        $absDelta = abs($delta);
        $pct = (float) $previous !== 0.0
            ? abs(($delta / (float) $previous) * 100)
            : ($absDelta > 0 ? 100.0 : 0.0);
        $formattedDelta = $isCurrency
            ? $this->formatCurrencyBr($absDelta)
            : $this->formatProjectReviewMetricValue($absDelta);
        $formattedPct = number_format($pct, 2, ',', '.');
        $trendSuffix = trim($suffix) !== '' ? " ({$suffix})" : '';
        $trend = sprintf('%s %s | %s%%%s', $prefix, $formattedDelta, $formattedPct, $trendSuffix);

        $isPositive = $direction === 'same'
            ? null
            : ($increaseIsPositive ? $direction === 'up' : $direction === 'down');

        $trendColor = $isPositive === null
            ? '#94a3b8'
            : ($isPositive ? '#22c55e' : '#ef4444');
        $cardBg = $isPositive === null
            ? 'rgba(255,255,255,.05)'
            : ($isPositive ? 'rgba(34,197,94,.09)' : 'rgba(239,68,68,.10)');
        $cardBorder = $isPositive === null
            ? 'rgba(255,255,255,.12)'
            : ($isPositive ? 'rgba(34,197,94,.35)' : 'rgba(239,68,68,.35)');

        return [
            'label' => $label,
            'value' => $value,
            'trend' => $trend,
            'trend_color' => $trendColor,
            'card_bg' => $cardBg,
            'card_border' => $cardBorder,
        ];
    }

    private function formatProjectReviewMetricValue(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0, ',', '.')
            : number_format($value, 2, ',', '.');
    }

    private function formatCurrencyBr(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
