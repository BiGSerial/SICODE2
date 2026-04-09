<?php

namespace App\Services\Reports;

use App\Custom\RuleBuilder;
use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\Wall;
use App\Models\WallScreen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductionWallV2DataService
{
    public const KEY_ROTATION_SECONDS = 'wall_v2_rotation_seconds';
    public const KEY_REFRESH_SECONDS = 'wall_v2_refresh_seconds';

    public function __construct(private AdsRequestedReportService $adsService)
    {
    }

    public function getPayloadForWall(int $wallId, ?int $screenId = null): array
    {
        $cacheKey = sprintf('wall_v2_payload:%d:%s', $wallId, $screenId ?? 'all');
        $ttlSeconds = max(3, min(20, (int) floor($this->refreshSeconds() / 2)));

        return Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function () use ($wallId, $screenId) {
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
                'screens' => $screens->map(fn (WallScreen $screen) => $this->buildScreenPayload($screen))->values()->all(),
            ];
        });
    }

    public function rotationSeconds(): int
    {
        return max(10, (int) (SystemSetting::getValue(self::KEY_ROTATION_SECONDS, '180') ?? '180'));
    }

    public function refreshSeconds(): int
    {
        return max(10, (int) (SystemSetting::getValue(self::KEY_REFRESH_SECONDS, '60') ?? '60'));
    }

    public function getItemChartsPayload(int $wallId, int $screenId, string $serviceId, ?string $component = null): array
    {
        $payload = $this->getPayloadForWall($wallId, $screenId);
        $screen = $payload['screens'][0] ?? null;
        if (!$screen) {
            return [
                'screen_id' => $screenId,
                'service_id' => $serviceId,
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component' => $component,
                'charts' => [],
            ];
        }

        $items = collect($screen['items'] ?? []);
        $item = $items->firstWhere('service_id', $serviceId);

        if (!$item) {
            return [
                'screen_id' => (int) ($screen['id'] ?? $screenId),
                'service_id' => $serviceId,
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component' => $component,
                'charts' => [],
            ];
        }

        if ($component) {
            return [
                'screen_id' => (int) ($screen['id'] ?? $screenId),
                'service_id' => (string) ($item['service_id'] ?? $serviceId),
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component' => $component,
                'data' => $this->extractItemComponent($item, $component),
            ];
        }

        return [
            'screen_id' => (int) ($screen['id'] ?? $screenId),
            'service_id' => (string) ($item['service_id'] ?? $serviceId),
            'updated_at' => now()->format('d/m/Y H:i:s'),
            'cards' => $item['cards'] ?? [],
            'week' => $item['week'] ?? null,
            'previous_service_name' => $item['previous_service_name'] ?? null,
            'charts' => [
                'queue_histogram' => $item['queue_histogram'] ?? ['labels' => [], 'values' => []],
                'production_open_histogram' => $item['production_open_histogram'] ?? ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                'production_daily' => $item['production_daily'] ?? ['labels' => [], 'assigned' => [], 'delivered' => []],
                'internal_return_donut' => $item['internal_return_donut'] ?? ['labels' => [], 'values' => []],
                'recent_completed' => $item['recent_completed'] ?? [],
                'ads_dashboard' => $item['ads_dashboard'] ?? null,
            ],
        ];
    }

    private function extractItemComponent(array $item, string $component): mixed
    {
        return match ($component) {
            'cards' => $item['cards'] ?? [],
            'week' => $item['week'] ?? null,
            'previous_service_name' => $item['previous_service_name'] ?? null,
            'queue_histogram' => $item['queue_histogram'] ?? ['labels' => [], 'values' => []],
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
        $screenType = (string) ($screen->screen_type ?: 'production_services');
        $screenConfig = (array) ($screen->screen_config ?? []);
        $fixedChart = (string) ($screenConfig['fixed_chart'] ?? '');

        if ($screenType === 'ads_chart') {
            $screenType = 'fixed_chart';
            $fixedChart = 'ads_dashboard';
        }

        if ($screenType === 'fixed_chart') {
            $item = $fixedChart === 'ads_dashboard'
                ? $this->buildAdsItemPayload($screen)
                : $this->buildFixedPlaceholderItemPayload($fixedChart);

            return [
                'id' => (int) $screen->id,
                'name' => (string) $screen->name,
                'screen_type' => $screenType,
                'duration_seconds' => (int) ($screen->duration_seconds ?: $this->rotationSeconds()),
                'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
                'items' => [$item],
            ];
        }

        return [
            'id' => (int) $screen->id,
            'name' => (string) $screen->name,
            'screen_type' => 'production_services',
            'duration_seconds' => (int) ($screen->duration_seconds ?: $this->rotationSeconds()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'items' => $screen->items
                ->filter(fn ($item) => $item->service)
                ->map(fn ($item) => $this->buildItemPayload($item->service, $item->previousService, (bool) $item->use_rule_builder))
                ->values()
                ->all(),
        ];
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
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily' => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut' => ['labels' => [], 'values' => []],
            'recent_completed' => [],
            'week' => null,
        ];
    }

    private function buildAdsItemPayload(WallScreen $screen): array
    {
        $filters = [
            'date_in' => null,
            'date_out' => null,
            'completed_in' => null,
            'completed_out' => null,
            'status_exact' => null,
            'search' => null,
            'companyIds' => [],
            'statusFilter' => 'all',
            'chart_granularity' => null,
        ];

        $summary = $this->adsService->summarize($filters);
        $flow = $this->adsService->demandVsDeliverySeries($filters);
        $queue = $this->adsService->queueDonutSeries($filters);
        $reuse = $this->adsService->reuseEconomyDonutSeries($filters);

        $reuseLabels = [
            'Solicitações Reaproveitadas',
            'Novas Solicitações',
        ];
        $reuseValues = [
            (int) ($reuse['reused'] ?? 0),
            (int) ($reuse['queued'] ?? 0),
        ];
        $reuseColors = ['rgba(5,150,105,0.85)', 'rgba(59,130,246,0.8)'];

        $lineMeanOpen = (float) ($flow['analytics']['backlog_avg'] ?? 0);
        $lineMeanOverdue = (float) ($flow['analytics']['overdue_avg'] ?? 0);
        $labelCount = max(1, count($flow['labels'] ?? []));

        $lineChart = [
            'labels' => $flow['labels'] ?? [],
            'datasets' => [
                [
                    'label' => 'Acumulado em aberto',
                    'data' => $flow['open_backlog'] ?? [],
                    'borderColor' => '#7c3aed',
                    'backgroundColor' => 'rgba(124,58,237,.2)',
                    'pointBackgroundColor' => '#7c3aed',
                    'tension' => 0.25,
                    'fill' => false,
                ],
                [
                    'label' => 'Atrasadas (>24h)',
                    'data' => $flow['overdue_backlog'] ?? [],
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
            'labels' => $flow['labels'] ?? [],
            'datasets' => [
                [
                    'label' => 'Demandas (solicitadas)',
                    'data' => $flow['requested'] ?? [],
                    'backgroundColor' => 'rgba(59,130,246,.8)',
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Saídas (concluídas)',
                    'data' => $flow['delivered'] ?? [],
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
            'previous_done' => (int) ($flow['analytics']['delivered_total'] ?? 0),
            'previous_ready' => (int) ($flow['analytics']['requested_total'] ?? 0),
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
                    ['label' => 'Solicitadas', 'value' => (int) ($flow['analytics']['requested_total'] ?? 0)],
                    ['label' => 'Concluídas', 'value' => (int) ($flow['analytics']['delivered_total'] ?? 0)],
                    ['label' => 'Taxa de conclusão', 'value' => number_format((float) ($flow['analytics']['completion_rate'] ?? 0), 1, ',', '.') . '%'],
                    ['label' => 'Abertas agora', 'value' => (int) ($flow['analytics']['current_open'] ?? 0)],
                    ['label' => 'Atrasadas agora', 'value' => (int) ($flow['analytics']['current_overdue'] ?? 0)],
                    ['label' => 'Média em aberto', 'value' => number_format((float) ($flow['analytics']['backlog_avg'] ?? 0), 1, ',', '.')],
                    ['label' => 'Pico em aberto', 'value' => (int) ($flow['analytics']['backlog_peak'] ?? 0)],
                    ['label' => 'Média atrasadas', 'value' => number_format((float) ($flow['analytics']['overdue_avg'] ?? 0), 1, ',', '.')],
                ],
                'line_chart' => $lineChart,
                'bar_chart' => $barChart,
                'queue_donut' => $queueDonut,
                'reuse_donut' => $reuseDonut,
            ],
            'queue_histogram' => [
                'labels' => [],
                'values' => [],
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

    private function buildItemPayload(Service $service, ?Service $previousService, bool $useRuleBuilder): array
    {
        [$start, $end] = $this->weeklyWindow();
        $dayLabels = $this->dailyDateLabels($start, $end);

        $queueQuery = $this->buildActivityQueueQuery($service, $useRuleBuilder)
            ->where('notes.type_note', 1);

        $queueTotal = (clone $queueQuery)->count();
        $queueHistogram = $this->buildAgeHistogram(
            $queueQuery,
            'COALESCE(notes.dt_status, notes.created_at)'
        );

        $productionOpenQuery = Production::query()
            ->where('service_id', $service->uuid)
            ->where('rejected', false)
            ->where('completed', false)
            ->whereNotNull('att_at');

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
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('reclaims')
                    ->whereColumn('reclaims.production_id', 'productions.id')
                    ->where('reclaims.completed', false);
            });

        $productionOpenNormalQuery = (clone $productionOpenQuery)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('reclaims')
                    ->whereColumn('reclaims.production_id', 'productions.id')
                    ->where('reclaims.completed', false);
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
        $internalTypes = $this->buildInternalReturnTypesDonut($service->uuid, $start, $end);

        $previousDone = 0;
        $previousReady = 0;

        if ($previousService) {
            $previousDoneQuery = Production::query()
                ->where('service_id', $previousService->uuid)
                ->where('rejected', false)
                ->where('completed', true)
                ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

            $previousDone = (clone $previousDoneQuery)->count();

            $previousReady = (clone $previousDoneQuery)
                ->whereNotExists(function ($q) use ($service, $end) {
                    $q->select(DB::raw(1))
                        ->from('productions as p2')
                        ->whereColumn('p2.note_id', 'productions.note_id')
                        ->where('p2.service_id', $service->uuid)
                        ->where('p2.rejected', false)
                        ->where('p2.created_at', '<=', $end->copy()->endOfDay());
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
                'queue_total' => (int) $queueTotal,
                'in_analysis' => (int) $openAssigned,
                'returned' => (int) $internalOpen,
                'previous_done' => (int) $previousDone,
                'previous_ready' => (int) $previousReady,
                'queue_ov_only' => true,
            ],
            'queue_histogram' => $queueHistogram,
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

    private function buildActivityQueueQuery(Service $service, bool $useRuleBuilder): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        if ($useRuleBuilder) {
            $service->loadMissing('Status');
            RuleBuilder::applyRules($query, $service->Status);
        } else {
            $query->where('nstats', $service->status);
        }

        $query->where(function ($q) use ($service) {
            $q->doesntHave('Productions')
                ->orWhereDoesntHave('Productions', function ($subquery) use ($service) {
                    $subquery->where('service_id', $service->uuid)
                        ->where('confirmed', false);
                });
        });

        return $query;
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
        $refDays = (clone $baseQuery)
            ->selectRaw('DATE(' . $sqlDateExpr . ') as ref_day')
            ->pluck('ref_day')
            ->filter();

        $today = now()->startOfDay();
        foreach ($refDays as $refDay) {
            try {
                $ref = Carbon::parse($refDay)->startOfDay();
                $diff = $ref->diffInDays($today, false);
                $age = $diff < 0 ? 0 : $diff;
                $bucket = $age >= $maxDays ? ($maxDays . '+') : (string) $age;
                $buckets[$bucket] = (int) ($buckets[$bucket] ?? 0) + 1;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return [
            'labels' => $labels,
            'values' => array_map(fn ($label) => (int) ($buckets[$label] ?? 0), $labels),
        ];
    }

    private function buildProductionDailyFlow(string $serviceId, array $dayLabels, Carbon $start, Carbon $end): array
    {
        $assigned = Production::query()
            ->where('service_id', $serviceId)
            ->where('rejected', false)
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

    private function buildInternalReturnTypesDonut(string $serviceId, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('productions')
            ->join('reclaims', function ($join) {
                $join->on('reclaims.production_id', '=', 'productions.id')
                    ->where('reclaims.completed', '=', false);
            })
            ->leftJoin('subcategories', 'subcategories.id', '=', 'reclaims.subcategory_id')
            ->where('productions.service_id', $serviceId)
            ->where('productions.rejected', false)
            ->where('productions.completed', false)
            ->whereNotNull('productions.att_at')
            ->whereBetween('productions.att_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw("COALESCE(NULLIF(subcategories.name, ''), NULLIF(reclaims.category, ''), 'SEM TIPO') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        $values = $rows->pluck('total')->map(fn ($v) => (int) $v)->all();
        $total = array_sum($values);
        $relation = array_map(
            fn ($v) => $total > 0 ? round((((int) $v) / $total) * 100, 1) : 0.0,
            $values
        );

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $values,
            'relation' => $relation,
            'total' => (int) $total,
        ];
    }
}
