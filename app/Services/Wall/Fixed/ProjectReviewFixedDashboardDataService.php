<?php

namespace App\Services\Wall\Fixed;

use App\Custom\Notestatus;
use App\Models\Production;
use App\Models\SystemSetting;
use App\Models\Wall;
use App\Models\WallScreen;
use App\Services\Wall\Context\ScreenContextResolver;
use App\Services\Wall\Support\CacheLockTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProjectReviewFixedDashboardDataService
{
    use CacheLockTrait;

    private ScreenContextResolver $contextResolver;

    public function __construct()
    {
        $this->contextResolver = new ScreenContextResolver();
    }

    // -------------------------------------------------------------------------
    // Endpoint dedicado: GET /fixed/project-review
    // -------------------------------------------------------------------------

    public function getPayload(int $wallId, int $screenId, ?string $component = null): array
    {
        $screen = $this->fetchScreen($wallId, $screenId);
        if (!$screen) {
            return ['screen_id' => $screenId, 'service_id' => 'fixed-project_review_dashboard', 'updated_at' => now()->format('d/m/Y H:i:s'), 'component' => $component, 'charts' => []];
        }

        $context = $this->contextResolver->resolve($screen);
        if (!$context->isFixed() || $context->fixedChart !== 'project_review_dashboard') {
            return ['screen_id' => (int) $screen->id, 'service_id' => 'fixed-project_review_dashboard', 'updated_at' => now()->format('d/m/Y H:i:s'), 'component' => $component, 'charts' => []];
        }

        $item = $this->buildItemPayload($screen);

        if ($component) {
            return [
                'screen_id'  => (int) $screen->id,
                'service_id' => 'fixed-project_review_dashboard',
                'updated_at' => now()->format('d/m/Y H:i:s'),
                'component'  => $component,
                'data'       => match ($component) {
                    'cards'                    => $item['cards'] ?? [],
                    'project_review_dashboard' => $item['project_review_dashboard'] ?? null,
                    default                    => null,
                },
            ];
        }

        return [
            'screen_id'            => (int) $screen->id,
            'service_id'           => 'fixed-project_review_dashboard',
            'updated_at'           => now()->format('d/m/Y H:i:s'),
            'cards'                => $item['cards'] ?? [],
            'week'                 => $item['week'] ?? null,
            'previous_service_name' => null,
            'charts' => [
                'project_review_dashboard'  => $item['project_review_dashboard'] ?? null,
                'queue_histogram'           => $item['queue_histogram'] ?? ['labels' => [], 'values' => []],
                'note_type_donut'           => $item['note_type_donut'] ?? ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
                'production_open_histogram' => $item['production_open_histogram'] ?? ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                'production_daily'          => $item['production_daily'] ?? ['labels' => [], 'assigned' => [], 'delivered' => []],
                'internal_return_donut'     => $item['internal_return_donut'] ?? ['labels' => [], 'values' => []],
                'recent_completed'          => $item['recent_completed'] ?? [],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Payload do item (usado pelo FixedChartScreenDataService via WallDataOrchestrator)
    // -------------------------------------------------------------------------

    public function buildItemPayload(WallScreen $screen): array
    {
        $cacheKey   = sprintf('wall_v2:fixed:project_review:screen:%d', (int) $screen->id);
        $ttlSeconds = $this->cacheSeconds();

        return $this->rememberWithOptionalLock($cacheKey, $ttlSeconds, function () use ($screen) {
            return $this->compute($screen);
        });
    }

    public function buildManifestItem(): array
    {
        return [
            'service_id'                => 'fixed-project_review_dashboard',
            'service_name'              => 'ANALISE DE PROJETO',
            'previous_service_id'       => null,
            'previous_service_name'     => null,
            'ads_chart'                 => ['kind' => 'dashboard'],
            'cards'                     => [],
            'queue_histogram'           => ['labels' => [], 'values' => []],
            'note_type_donut'           => ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily'          => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut'     => ['labels' => [], 'values' => []],
            'recent_completed'          => [],
            'week'                      => null,
        ];
    }

    // -------------------------------------------------------------------------
    // Lógica de negócio
    // -------------------------------------------------------------------------

    private function compute(WallScreen $screen): array
    {
        $t0      = microtime(true);
        $timings = [];
        $measure = function (string $label, callable $fn) use (&$timings) {
            $t = microtime(true);
            $r = $fn();
            $timings[$label] = round((microtime(true) - $t) * 1000, 1);
            return $r;
        };

        [$periodStart, $periodEnd, $prevStart, $prevEnd] = $this->comparisonWindows();

        $queueBase  = $this->queueBaseQuery();
        $pendingBase = $this->pendingQuery();

        $returnIds             = $measure('return_ids',             fn () => $this->returnProductionIds());
        $pendingTotal          = $measure('pending_total',           fn () => (clone $queueBase)->count());
        $pendingWithCycle      = $measure('pending_with_cycle',      fn () => (clone $pendingBase)->count());
        $pendingWithoutCycle   = max(0, $pendingTotal - $pendingWithCycle);
        $pendingReturn         = $measure('pending_return',          fn () => $returnIds->isEmpty() ? 0 : (clone $pendingBase)->whereIn('productions.id', $returnIds)->count());

        $pendingCycleCurrent      = $measure('cycle_current', fn () => $this->queueEntriesCountInRange($periodStart, $periodEnd));
        $pendingCyclePrev         = $measure('cycle_prev',    fn () => $this->queueEntriesCountInRange($prevStart, $prevEnd));
        $pendingReturnCycleCurrent = $measure('ret_cycle_cur', fn () => $this->pendingCycleCountInRange($periodStart, $periodEnd, $returnIds));
        $pendingReturnCyclePrev    = $measure('ret_cycle_prev', fn () => $this->pendingCycleCountInRange($prevStart, $prevEnd, $returnIds));
        $pendingInitialTotal  = max(0, $pendingWithCycle - $pendingReturn);

        $histAssociated   = $measure('hist_assoc',          fn () => $this->associatedNotFinalizedHistogram());
        $analyzedCurrent  = $measure('analyzed_cur',        fn () => $this->analyzedCountInRange($periodStart, $periodEnd));
        $analyzedPrev     = $measure('analyzed_prev',       fn () => $this->analyzedCountInRange($prevStart, $prevEnd));
        $avgHoursCurrent  = $measure('avg_hours_cur',       fn () => $this->averageDecisionHoursInRange($periodStart, $periodEnd));
        $avgHoursPrev     = $measure('avg_hours_prev',      fn () => $this->averageDecisionHoursInRange($prevStart, $prevEnd));
        $histogram        = $measure('hist_unassoc',        fn () => $this->unassociatedQueueHistogram());
        $costCurrent      = $measure('cost_cur',            fn () => $this->costSummaryForWindow($periodStart, $periodEnd));
        $costPrev         = $measure('cost_prev',           fn () => $this->costSummaryForWindow($prevStart, $prevEnd));
        $decisionDonut    = $measure('donut_decision',      fn () => $this->analyzedDecisionDonutCurrentMonth($periodStart, $periodEnd));
        $recentProds      = $measure('recent_prods',        fn () => $this->recentProductionsList(30));

        $plannedTotal    = (float) ($costCurrent['planned_total_cost'] ?? 0);
        $plannedPrev     = (float) ($costPrev['planned_total_cost'] ?? 0);
        $increaseTotal   = (float) ($costCurrent['increase_total_cost'] ?? 0);
        $increasePrev    = (float) ($costPrev['increase_total_cost'] ?? 0);
        $economyTotal    = (float) ($costCurrent['economy_total_cost'] ?? 0);
        $economyPrev     = (float) ($costPrev['economy_total_cost'] ?? 0);
        $maintained      = (int)   ($costCurrent['maintained_orders_count'] ?? 0);
        $maintainedPrev  = (int)   ($costPrev['maintained_orders_count'] ?? 0);
        // Mantém consistência visual com o dashboard original:
        // Revisado = Planejado + Acréscimos - Reduções.
        $revisedTotal    = round($plannedTotal + $increaseTotal - $economyTotal, 2);
        $revisedPrev     = round($plannedPrev + $increasePrev - $economyPrev, 2);
        $netVariation    = round($increaseTotal - $economyTotal, 2);
        $netAbs          = abs($netVariation);
        $netPct          = $plannedTotal > 0 ? ($netAbs / $plannedTotal) * 100 : 0.0;
        $netUp           = $netVariation >= 0;
        $companyPlanned  = (float) ($costCurrent['planned_company_total_cost'] ?? 0);
        $companyDelta    = (float) ($costCurrent['company_net_variation_cost'] ?? 0);
        $companyDeltaAbs = abs($companyDelta);
        $companyDeltaPct = $companyPlanned > 0 ? ($companyDeltaAbs / $companyPlanned) * 100 : 0.0;
        $companyUp       = $companyDelta >= 0;
        $clientPlanned   = (float) ($costCurrent['planned_client_total_cost'] ?? 0);
        $clientDelta     = (float) ($costCurrent['client_net_variation_cost'] ?? 0);
        $clientDeltaAbs  = abs($clientDelta);
        $clientDeltaPct  = $clientPlanned > 0 ? ($clientDeltaAbs / $clientPlanned) * 100 : 0.0;
        $clientUp        = $clientDelta >= 0;
        $revisedCompany  = (float) ($costCurrent['revised_company_total_cost'] ?? 0);
        $revisedClient   = (float) ($costCurrent['revised_client_total_cost'] ?? 0);

        $lineLabels = $histogram['labels'];
        $lineValues = $histogram['values'];
        $hasData    = (
            array_sum(array_map('intval', $lineValues)) > 0
            || array_sum(array_map('intval', (array) ($histAssociated['values'] ?? []))) > 0
            || (int) ($decisionDonut['total'] ?? 0) > 0
            || !empty($recentProds)
        );

        $totalMs = round((microtime(true) - $t0) * 1000, 1);
        if ($totalMs >= 1500) {
            Log::warning('wall project-review payload slow', ['screen_id' => (int) $screen->id, 'total_ms' => $totalMs, 'steps_ms' => $timings]);
        }

        return [
            'service_id'           => 'fixed-project_review_dashboard',
            'service_name'         => 'ANALISE DE PROJETO',
            'previous_service_id'  => null,
            'previous_service_name' => null,
            'cards' => [
                'queue_total'   => (int) $pendingTotal,
                'queue_ov'      => 0,
                'queue_notes'   => 0,
                'returned'      => (int) $pendingReturn,
                'previous_done' => (int) $analyzedCurrent,
                'next_entry'    => 0,
            ],
            'ads_chart' => ['kind' => 'dashboard', 'title' => 'ANALISE DE PROJETO', 'labels' => [], 'datasets' => []],
            'project_review_dashboard' => [
                'subtitle'          => sprintf('Período vigente %s a %s | Comparativo %s a %s | Gráficos: últimos 15 dias', $periodStart->format('d/m'), $periodEnd->format('d/m'), $prevStart->format('d/m'), $prevEnd->format('d/m')),
                'line_chart_title'  => 'Pilha a Analisar (sem análise associada, dias na pilha)',
                'bar_chart_title'   => 'Em análise não finalizado (dias desde devolução do analista)',
                'queue_donut_title' => 'Composição dos projetos analisados',
                'reuse_donut_title' => 'Últimas Atualizações em Produções',
                'has_data'          => $hasData,
                'top_cards' => [
                    array_merge(
                        $this->kpiCard('Valor planejado', $this->fmt($plannedTotal), $plannedTotal, $plannedPrev, 'vs mês anterior', false, true),
                        ['formula_operator_after' => '+']
                    ),
                    array_merge(
                        $this->kpiCard('Somatório de acréscimos', $this->fmt($increaseTotal), $increaseTotal, $increasePrev, 'vs mês anterior', false, true),
                        ['formula_operator_after' => '-']
                    ),
                    array_merge(
                        $this->kpiCard('Somatório de reduções', $this->fmt($economyTotal), $economyTotal, $economyPrev, 'vs mês anterior', true, true),
                        ['formula_operator_after' => '=']
                    ),
                    $this->kpiCard('Valor revisado total', $this->fmt($revisedTotal), $revisedTotal, $revisedPrev, 'vs mês anterior', false, true),
                    ['label' => 'Fila atual', 'value' => (string) $pendingTotal],
                ],
                'middle_cards' => [
                    ['label' => 'Diferença líquida (acréscimos - reduções)', 'value' => $this->fmt($netAbs), 'trend' => sprintf('%s %s%%', $netUp ? '↑' : '↓', number_format($netPct, 2, ',', '.')), 'trend_color' => $netUp ? '#ef4444' : '#22c55e', 'card_bg' => $netUp ? 'rgba(239,68,68,.10)' : 'rgba(34,197,94,.10)', 'card_border' => $netUp ? 'rgba(239,68,68,.35)' : 'rgba(34,197,94,.35)', 'inline_trend' => true],
                    ['label' => 'Custo empresa (planejado x revisado)', 'value' => $this->fmt($companyDeltaAbs), 'trend' => sprintf('%s %s%%', $companyUp ? '↑' : '↓', number_format($companyDeltaPct, 2, ',', '.')), 'trend_color' => $companyUp ? '#ef4444' : '#22c55e', 'card_bg' => $companyUp ? 'rgba(239,68,68,.10)' : 'rgba(34,197,94,.10)', 'card_border' => $companyUp ? 'rgba(239,68,68,.35)' : 'rgba(34,197,94,.35)', 'inline_trend' => true],
                    ['label' => 'Custo cliente (planejado x revisado)', 'value' => $this->fmt($clientDeltaAbs), 'trend' => sprintf('%s %s%%', $clientUp ? '↑' : '↓', number_format($clientDeltaPct, 2, ',', '.')), 'trend_color' => $clientUp ? '#ef4444' : '#22c55e', 'card_bg' => $clientUp ? 'rgba(239,68,68,.10)' : 'rgba(34,197,94,.10)', 'card_border' => $clientUp ? 'rgba(239,68,68,.35)' : 'rgba(34,197,94,.35)', 'inline_trend' => true],
                    $this->kpiCard('Sem análise associada', (string) $pendingWithoutCycle, $pendingWithoutCycle, 0, 'status 30 sem ciclo', false),
                    $this->kpiCard('Entradas no período',   (string) $pendingCycleCurrent,  $pendingCycleCurrent,  $pendingCyclePrev,  'MTD'),
                    $this->kpiCard('Retorno na fila',       (string) $pendingReturn,         $pendingReturnCycleCurrent, $pendingReturnCyclePrev, 'MTD retornos', false),
                    $this->kpiCard('Decisões no período',   (string) $analyzedCurrent,       $analyzedCurrent, $analyzedPrev,  'MTD respostas'),
                    $this->kpiCard('Tempo médio envio > decisão', number_format($avgHoursCurrent, 1, ',', '.') . 'h', $avgHoursCurrent, $avgHoursPrev, 'MTD (horas)', false),
                ],
                'line_chart' => [
                    'labels'   => $lineLabels,
                    'datasets' => [['label' => 'Pilha a Analisar', 'data' => $lineValues, 'borderColor' => '#f59e0b', 'backgroundColor' => 'rgba(245,158,11,.22)', 'pointBackgroundColor' => '#f59e0b', 'tension' => 0.2, 'fill' => false, 'borderWidth' => 1]],
                ],
                'bar_chart' => [
                    'labels'   => $histAssociated['labels'],
                    'datasets' => [['label' => 'Em análise não finalizado', 'data' => $histAssociated['values'], 'backgroundColor' => 'rgba(56,189,248,.72)', 'borderColor' => 'rgba(56,189,248,1)', 'borderWidth' => 1]],
                ],
                'queue_donut' => ['labels' => $decisionDonut['labels'], 'values' => $decisionDonut['values'], 'colors' => $decisionDonut['colors'], 'total' => (int) ($decisionDonut['total'] ?? 0)],
                'reuse_donut' => ['labels' => ['Revisado empresa', 'Revisado cliente'], 'values' => [round($revisedCompany, 2), round($revisedClient, 2)], 'colors' => ['rgba(20,184,166,.82)', 'rgba(14,165,233,.82)'], 'total' => round($revisedCompany + $revisedClient, 2), 'reuse_rate' => 0],
                'recent_productions' => $recentProds,
            ],
            'queue_histogram'           => ['labels' => [], 'values' => []],
            'note_type_donut'           => ['labels' => ['Com produção', 'Sem produção'], 'values' => [0, 0], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily'          => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut'     => ['labels' => [], 'values' => []],
            'recent_completed'          => [],
            'week' => [
                'start' => $periodStart->toDateString(),
                'end'   => $periodEnd->toDateString(),
                'label' => sprintf('%s a %s', $periodStart->format('d/m'), $periodEnd->format('d/m')),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Queries de dados
    // -------------------------------------------------------------------------

    private function pendingQuery(): Builder
    {
        return Production::query()
            ->where('status', Production::STATUS_IN_PROJECT_REVIEW)
            ->whereHas('ProjectReviewCycles');
    }

    private function queueBaseQuery(): Builder
    {
        return Production::query()->where('status', Production::STATUS_IN_PROJECT_REVIEW);
    }

    private function returnProductionIds(): Collection
    {
        $rejectedStatus = (int) Production::STATUS_REJECTED_PROJECT_REVIEW;

        $cycleIds = DB::table('project_review_cycles')
            ->where(fn ($q) => $q->where('round_number', '>', 1)->orWhere('decision', 'REJECTED'))
            ->distinct()->pluck('production_id');

        $timelineIds = DB::table('notetimelines')
            ->where('status', $rejectedStatus)
            ->distinct()->pluck('production_id');

        return $cycleIds->merge($timelineIds)
            ->filter(fn ($id) => !is_null($id))
            ->map(fn ($id) => (int) $id)
            ->unique()->values();
    }

    private function comparisonWindows(): array
    {
        $periodStart = now()->startOfMonth();
        $periodEnd   = now()->endOfDay();
        $daysSpan    = max(1, $periodStart->copy()->startOfDay()->diffInDays($periodEnd->copy()->startOfDay()) + 1);
        $prevStart   = $periodStart->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEndCand = $prevStart->copy()->addDays($daysSpan - 1)->endOfDay();
        $prevMonthEnd = $prevStart->copy()->endOfMonth();
        $prevEnd     = $prevEndCand->gt($prevMonthEnd) ? $prevMonthEnd : $prevEndCand;

        return [$periodStart, $periodEnd, $prevStart, $prevEnd];
    }

    private function queueEntriesCountInRange(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('project_review_cycles as cy')
            ->whereNotNull('cy.submitted_at')
            ->whereBetween('cy.submitted_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count('cy.id');
    }

    private function pendingCycleCountInRange(Carbon $start, Carbon $end, ?Collection $returnIds = null): int
    {
        $query = DB::table('project_review_cycles as cy')
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->where('p.status', Production::STATUS_IN_PROJECT_REVIEW)
            ->where('cy.decision', 'PENDING')
            ->whereNotNull('cy.submitted_at')
            ->whereBetween('cy.submitted_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        if (!is_null($returnIds)) {
            if ($returnIds->isEmpty()) return 0;
            $query->whereIn('p.id', $returnIds);
        }

        return (int) $query->count();
    }

    private function analyzedCountInRange(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('project_review_cycles')
            ->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->whereNotNull('decided_at')
            ->whereBetween('decided_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count();
    }

    private function averageDecisionHoursInRange(Carbon $start, Carbon $end): float
    {
        return round((float) DB::table('project_review_cycles')
            ->whereNotNull('submitted_at')
            ->whereNotNull('decided_at')
            ->whereBetween('decided_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, decided_at)) as avg_hours')
            ->value('avg_hours'), 1);
    }

    private function unassociatedQueueHistogram(): array
    {
        $max    = 30;
        $labels = [...array_map('strval', range(0, $max - 1)), "{$max}+"];
        $ageExpr = "GREATEST(0, DATEDIFF(CURDATE(), DATE(p.completed_at)))";
        $totals = DB::table('productions as p')
            ->join('project_review_cycles as cy', 'cy.production_id', '=', 'p.id')
            ->where('p.status', Production::STATUS_IN_PROJECT_REVIEW)
            ->where('cy.decision', 'PENDING')
            ->whereNotNull('p.completed_at')
            ->whereRaw('(
                SELECT COUNT(*)
                FROM project_review_cycles c2
                WHERE c2.production_id = p.id
            ) = 1')
            ->selectRaw("CASE WHEN {$ageExpr} >= {$max} THEN '{$max}+' ELSE CAST({$ageExpr} AS CHAR) END as bucket, COUNT(DISTINCT p.id) as total")
            ->groupBy('bucket')->pluck('total', 'bucket')
            ->map(fn ($v) => (int) $v)->all();

        return ['labels' => $labels, 'values' => array_map(fn ($l) => (int) ($totals[$l] ?? 0), $labels)];
    }

    private function associatedNotFinalizedHistogram(int $max = 30): array
    {
        $labels = [...array_map('strval', range(0, $max - 1)), "{$max}+"];
        $sub    = DB::table('project_review_cycles as cy')
            ->selectRaw('cy.production_id, MAX(cy.decided_at) as last_returned_at')
            ->where('cy.decision', 'REJECTED')->whereNotNull('cy.decided_at')
            ->groupBy('cy.production_id');

        $totals = DB::table('productions as p')
            ->joinSub($sub, 'lr', fn ($j) => $j->on('lr.production_id', '=', 'p.id'))
            ->whereNotIn('p.status', [5, Production::STATUS_IN_PROJECT_REVIEW])
            ->selectRaw("CASE WHEN GREATEST(0, DATEDIFF(CURDATE(), DATE(lr.last_returned_at))) >= {$max} THEN '{$max}+' ELSE CAST(GREATEST(0, DATEDIFF(CURDATE(), DATE(lr.last_returned_at))) AS CHAR) END as bucket, COUNT(*) as total")
            ->groupBy('bucket')->pluck('total', 'bucket')
            ->map(fn ($v) => (int) $v)->all();

        return ['labels' => $labels, 'values' => array_map(fn ($l) => (int) ($totals[$l] ?? 0), $labels)];
    }

    private function analyzedDecisionDonutCurrentMonth(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('project_review_cycles as cy')
            ->whereNotNull('cy.decided_at')
            ->whereBetween('cy.decided_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->whereIn('cy.decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->selectRaw("cy.decision as kind, COUNT(*) as total")
            ->groupBy('kind')->get();

        $rejected = 0;
        $approved = 0;
        $approvedWithRemarks = 0;
        foreach ($rows as $row) {
            $kind = (string) ($row->kind ?? '');
            if ($kind === 'REJECTED') {
                $rejected += (int) $row->total;
                continue;
            }
            if ($kind === 'APPROVED') {
                $approved += (int) $row->total;
                continue;
            }
            if ($kind === 'APPROVED_WITH_REMARKS') {
                $approvedWithRemarks += (int) $row->total;
            }
        }

        return [
            'labels' => ['Reprovados', 'Aprovados sem erro', 'Aprovados com ressalva'],
            'values' => [$rejected, $approved, $approvedWithRemarks],
            'colors' => ['rgba(239,68,68,.88)', 'rgba(59,130,246,.86)', 'rgba(245,158,11,.88)'],
            'total' => $rejected + $approved + $approvedWithRemarks,
        ];
    }

    private function recentProductionsList(int $limit = 30): array
    {
        return Production::query()
            ->with(['Note:id,note', 'User:id,name', 'Company:id,name'])
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('project_review_cycles as cy')->whereColumn('cy.production_id', 'productions.id'))
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (Production $p) {
                $meta = $this->statusMeta((int) ($p->status ?? 0));
                $referenceAt = $p->updated_at ?? $p->completed_at ?? $p->created_at;
                return [
                    'note'         => (string) ($p->Note?->note ?? '-'),
                    'user_name'    => $this->compactName((string) ($p->User?->name ?? '-')),
                    'company_name' => $this->compactCompany((string) ($p->Company?->name ?? '-')),
                    'status_id'    => (int) ($p->status ?? 0),
                    'status_label' => $meta['label'],
                    'status_color' => $meta['color'],
                    'reference_at' => optional($referenceAt)->format('d/m/Y H:i') ?? '-',
                ];
            })->values()->all();
    }

    private function costSummaryForWindow(Carbon $start, Carbon $end): array
    {
        $ids = DB::table('productions as p')
            ->join('project_review_cycles as cy', 'cy.production_id', '=', 'p.id')
            ->whereDate('cy.submitted_at', '>=', $start->toDateString())
            ->whereDate('cy.submitted_at', '<=', $end->toDateString())
            ->distinct()->pluck('p.id');

        return $this->costVariationSummary($ids);
    }

    private function costVariationSummary(Collection $ids): array
    {
        $empty = ['planned_total_cost' => 0, 'revised_total_cost' => 0, 'planned_company_total_cost' => 0, 'planned_client_total_cost' => 0, 'revised_company_total_cost' => 0, 'revised_client_total_cost' => 0, 'company_net_variation_cost' => 0, 'client_net_variation_cost' => 0, 'economy_total_cost' => 0, 'increase_total_cost' => 0, 'net_variation_cost' => 0, 'maintained_orders_count' => 0];
        if ($ids->isEmpty()) return $empty;

        $rows = DB::table('project_review_orders as o')
            ->join('project_review_cycles as cy', 'cy.id', '=', 'o.cycle_id')
            ->whereIn('cy.production_id', $ids)
            ->selectRaw('cy.production_id, cy.round_number, o.id as order_id, o.order_number, o.total_cost, o.company_cost, o.client_cost')
            ->orderBy('cy.production_id')->orderBy('cy.round_number')->orderBy('o.id')
            ->get();

        $planned = $revised = $pCompany = $pClient = $rCompany = $rClient = $economy = $increase = 0.0;
        $maintained = 0;

        $rows->groupBy('production_id')->each(function ($pRows) use (&$planned, &$revised, &$pCompany, &$pClient, &$rCompany, &$rClient, &$economy, &$increase, &$maintained) {
            $byRound = collect($pRows)->groupBy('round_number');
            if ($byRound->isEmpty()) return;

            $prefixSeries = $this->prefixSeriesByRound($byRound);
            $hasPrefixData = false;

            foreach (['170', '190', '150', '200'] as $prefix) {
                $series = collect($prefixSeries[$prefix] ?? [])->values();
                if ($series->isEmpty()) continue;
                $hasPrefixData = true;
                $first = (array) $series->first(); $last = (array) $series->last();
                $planned  += (float) ($first['total'] ?? 0); $revised  += (float) ($last['total'] ?? 0);
                $pCompany += (float) ($first['company'] ?? 0); $pClient += (float) ($first['client'] ?? 0);
                $rCompany += (float) ($last['company'] ?? 0); $rClient += (float) ($last['client'] ?? 0);
                if ($series->count() >= 2) {
                    $delta = round(((float) ($last['total'] ?? 0)) - ((float) ($first['total'] ?? 0)), 2);
                    if ($delta > 0) $increase += $delta; elseif ($delta < 0) $economy += abs($delta); else $maintained++;
                }
            }

            if (!$hasPrefixData) {
                $roundTotals = $byRound->sortKeys()->map(fn ($rr) => ['total' => (float) collect($rr)->sum('total_cost'), 'company' => (float) collect($rr)->sum('company_cost'), 'client' => (float) collect($rr)->sum('client_cost')])->values();
                $first = (array) $roundTotals->first(); $last = (array) $roundTotals->last();
                $planned  += (float) ($first['total'] ?? 0); $revised  += (float) ($last['total'] ?? 0);
                $pCompany += (float) ($first['company'] ?? 0); $pClient += (float) ($first['client'] ?? 0);
                $rCompany += (float) ($last['company'] ?? 0); $rClient += (float) ($last['client'] ?? 0);
                if ($roundTotals->count() >= 2) {
                    $delta = round(((float) ($last['total'] ?? 0)) - ((float) ($first['total'] ?? 0)), 2);
                    if ($delta > 0) $increase += $delta; elseif ($delta < 0) $economy += abs($delta); else $maintained++;
                }
            }
        });

        return ['planned_total_cost' => $planned, 'revised_total_cost' => $revised, 'planned_company_total_cost' => $pCompany, 'planned_client_total_cost' => $pClient, 'revised_company_total_cost' => $rCompany, 'revised_client_total_cost' => $rClient, 'company_net_variation_cost' => $rCompany - $pCompany, 'client_net_variation_cost' => $rClient - $pClient, 'economy_total_cost' => $economy, 'increase_total_cost' => $increase, 'net_variation_cost' => $revised - $planned, 'maintained_orders_count' => $maintained];
    }

    private function prefixSeriesByRound(Collection $byRound): array
    {
        $series = ['170' => [], '190' => [], '150' => [], '200' => []];
        $byRound->sortKeys()->each(function ($roundRows) use (&$series) {
            $latest = [];
            foreach (collect($roundRows) as $row) {
                $prefix = $this->extractPrefix((string) ($row->order_number ?? ''));
                if (!is_null($prefix)) $latest[$prefix] = ['total' => (float) ($row->total_cost ?? 0), 'company' => (float) ($row->company_cost ?? 0), 'client' => (float) ($row->client_cost ?? 0)];
            }
            foreach (array_keys($series) as $prefix) {
                if (array_key_exists($prefix, $latest)) $series[$prefix][] = $latest[$prefix];
            }
        });
        return $series;
    }

    private function extractPrefix(string $orderNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $orderNumber);
        if (!$digits || strlen($digits) < 3) return null;
        $prefix = substr($digits, 0, 3);
        return in_array($prefix, ['170', '190', '150', '200'], true) ? $prefix : null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function cacheSeconds(): int
    {
        $refresh = max(10, (int) (SystemSetting::getValue('wall_v2_refresh_seconds', '60') ?? '60'));
        return max(120, min(600, $refresh * 3));
    }

    private function fetchScreen(int $wallId, int $screenId): ?WallScreen
    {
        Wall::query()->where('enabled', true)->findOrFail($wallId);
        return WallScreen::query()->where('wall_id', $wallId)->whereKey($screenId)->first();
    }

    private function kpiCard(string $label, string $value, float|int $current, float|int $previous, string $suffix = '', bool $increaseIsPositive = true, bool $isCurrency = false): array
    {
        $delta     = (float) $current - (float) $previous;
        $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'same');
        $prefix    = $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '→');
        $absDelta  = abs($delta);
        $pct       = (float) $previous !== 0.0 ? abs($delta / (float) $previous * 100) : ($absDelta > 0 ? 100.0 : 0.0);
        $fmtDelta  = $isCurrency ? $this->fmt($absDelta) : $this->fmtMetric($absDelta);
        $trend     = sprintf('%s %s | %s%%%s', $prefix, $fmtDelta, number_format($pct, 2, ',', '.'), trim($suffix) !== '' ? " ({$suffix})" : '');
        $isPositive = $direction === 'same' ? null : ($increaseIsPositive ? $direction === 'up' : $direction === 'down');
        $trendColor = $isPositive === null ? '#94a3b8' : ($isPositive ? '#22c55e' : '#ef4444');
        $cardBg     = $isPositive === null ? 'rgba(255,255,255,.05)' : ($isPositive ? 'rgba(34,197,94,.09)' : 'rgba(239,68,68,.10)');
        $cardBorder = $isPositive === null ? 'rgba(255,255,255,.12)' : ($isPositive ? 'rgba(34,197,94,.35)' : 'rgba(239,68,68,.35)');
        return ['label' => $label, 'value' => $value, 'trend' => $trend, 'trend_color' => $trendColor, 'card_bg' => $cardBg, 'card_border' => $cardBorder];
    }

    private function fmt(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function fmtMetric(float $value): string
    {
        return fmod($value, 1.0) === 0.0 ? number_format($value, 0, ',', '.') : number_format($value, 2, ',', '.');
    }

    private function statusMeta(int $statusId): array
    {
        $meta  = Notestatus::status($statusId);
        $label = (string) ($meta->status ?? ('Status ' . $statusId));
        $color = match ((string) ($meta->color ?? 'secondary')) {
            'success' => '#22c55e', 'danger' => '#ef4444', 'warning' => '#f59e0b',
            'primary' => '#3b82f6', 'info'   => '#06b6d4', 'dark'    => '#94a3b8',
            default   => '#94a3b8',
        };
        return ['label' => $label, 'color' => $color];
    }

    private function compactName(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)), fn ($p) => $p !== ''));
        if (empty($parts)) return '-';
        return count($parts) === 1 ? $parts[0] : $parts[0] . ' ' . $parts[count($parts) - 1];
    }

    private function compactCompany(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)), fn ($p) => $p !== ''));
        if (empty($parts)) return '-';
        if (count($parts) === 1) return $parts[0];
        $initials = '';
        for ($i = 1; $i < count($parts); $i++) $initials .= mb_strtoupper(mb_substr($parts[$i], 0, 1));
        return $parts[0] . ' ' . $initials;
    }
}
