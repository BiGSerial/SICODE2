<?php

namespace App\Http\Livewire\Reports;

use App\Custom\Notestatus;
use App\Http\Livewire\Reports\Concerns\ReturnInternFilters;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnInternDashboard extends Component
{
    use ReturnInternFilters;

    protected $queryString = [
        'dt_in' => ['except' => '', 'as' => 'de'],
        'dt_out' => ['except' => '', 'as' => 'ate'],
        'search' => ['except' => '', 'as' => 'busca'],
        'originFilters' => ['except' => [], 'as' => 'origem'],
        'serviceIds' => ['except' => [], 'as' => 'srv'],
        'category' => ['except' => '', 'as' => 'cat'],
        'dispatcherUserId' => ['except' => '', 'as' => 'disp'],
        'productionUserId' => ['except' => '', 'as' => 'prod'],
        'companyId' => ['except' => '', 'as' => 'emp'],
        'productionStatus' => ['except' => '', 'as' => 'sts'],
        'completedFilter' => ['except' => '', 'as' => 'cmp'],
        'resolutionMin' => ['except' => '', 'as' => 'rmin'],
        'resolutionMax' => ['except' => '', 'as' => 'rmax'],
    ];

    public function updated($propertyName)
    {
        $paginationSensitive = [
            'dt_in',
            'dt_out',
            'search',
            'originFilters',
            'serviceIds',
            'category',
            'dispatcherUserId',
            'productionUserId',
            'companyId',
            'productionStatus',
            'completedFilter',
            'resolutionMin',
            'resolutionMax',
        ];

        $isOriginNested = str_starts_with($propertyName, 'originFilters.');
        $isServiceNested = str_starts_with($propertyName, 'serviceIds.');

        if ($isOriginNested || $isServiceNested || in_array($propertyName, $paginationSensitive, true)) {
            $this->dispatchCharts($this->buildCharts());
        }
    }

    protected function buildSummary(): array
    {
        $base = $this->baseReclaimQuery();

        $total = (clone $base)->count();
        $completed = (clone $base)->where('completed', true)->count();
        $open = max(0, $total - $completed);
        $withProduction = (clone $base)->whereNotNull('production_id')->count();

        $avgResolution = (clone $base)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, reclaims.created_at, reclaims.completed_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgReaction = (clone $base)
            ->join('productions as p', 'p.id', '=', 'reclaims.production_id')
            ->whereNotNull('p.att_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, reclaims.created_at, p.att_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgExecution = (clone $base)
            ->join('productions as p', 'p.id', '=', 'reclaims.production_id')
            ->whereNotNull('p.att_at')
            ->whereNotNull('p.completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, p.att_at, p.completed_at)) as avg_seconds')
            ->value('avg_seconds');

        return [
            'total' => (int) $total,
            'completed' => (int) $completed,
            'open' => (int) $open,
            'with_production' => (int) $withProduction,
            'avg_resolution_sec' => (int) $avgResolution,
            'avg_resolution_human' => $this->secondsToHuman((int) $avgResolution),
            'avg_reaction_sec' => (int) $avgReaction,
            'avg_reaction_human' => $this->secondsToHuman((int) $avgReaction),
            'avg_execution_sec' => (int) $avgExecution,
            'avg_execution_human' => $this->secondsToHuman((int) $avgExecution),
        ];
    }

    protected function buildOriginChart(): array
    {
        $baseIds = $this->baseIdSubquery();

        $viabilitySub = DB::table('reclaim_viability')->select('reclaim_id')->distinct();
        $waitingSub = DB::table('hiring_waitings')->select('reclaim_id')->whereNotNull('reclaim_id')->distinct();
        $approvalSub = DB::table('viability_approval_reclaim')->select('reclaim_id')->distinct();
        $externalSub = DB::table('external_reclaim')->select('reclaim_id')->distinct();

        $rows = DB::table('reclaims as r')
            ->leftJoinSub($viabilitySub, 'rv', 'rv.reclaim_id', '=', 'r.id')
            ->leftJoinSub($waitingSub, 'hw', 'hw.reclaim_id', '=', 'r.id')
            ->leftJoinSub($approvalSub, 'var', 'var.reclaim_id', '=', 'r.id')
            ->leftJoinSub($externalSub, 'er', 'er.reclaim_id', '=', 'r.id')
            ->whereIn('r.id', $baseIds)
            ->selectRaw("
                CASE
                    WHEN rv.reclaim_id IS NOT NULL THEN 'Viabilidade'
                    WHEN hw.reclaim_id IS NOT NULL THEN 'Contratacao'
                    WHEN var.reclaim_id IS NOT NULL THEN 'Aprovacao'
                    WHEN er.reclaim_id IS NOT NULL THEN 'Orgao Externo'
                    ELSE 'Sem Origem'
                END as origem,
                COUNT(DISTINCT r.id) as total
            ")
            ->groupBy('origem')
            ->orderByDesc('total')
            ->get();

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $rows->pluck('origem')->toArray(),
                'datasets' => [[
                    'label' => 'Origem',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                    'backgroundColor' => [
                        'rgba(15,118,110,.4)',
                        'rgba(37,99,235,.35)',
                        'rgba(245,158,11,.35)',
                        'rgba(239,68,68,.35)',
                        'rgba(148,163,184,.35)',
                    ],
                    'borderColor' => [
                        '#0f766e',
                        '#2563eb',
                        '#f59e0b',
                        '#ef4444',
                        '#94a3b8',
                    ],
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'right'],
                    'title' => ['display' => true, 'text' => 'Origem dos retornos internos'],
                ],
                'cutout' => '60%',
            ],
        ];
    }

    protected function buildDailyChart(): array
    {
        [$start, $end] = $this->getReturnInternDateRange();
        $rows = (clone $this->baseReclaimQuery())
            ->selectRaw('DATE(reclaims.created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $series = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $series[] = (int) ($rows[$key] ?? 0);
            $cursor->addDay();
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Retornos criados',
                    'data' => $series,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37,99,235,.2)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.2,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title' => ['display' => true, 'text' => 'Retornos internos por dia'],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Quantidade'],
                    ],
                ],
            ],
        ];
    }

    protected function buildTopCompaniesChart(): array
    {
        $baseIds = $this->baseIdSubquery();

        $rows = DB::table('reclaims as r')
            ->leftJoin('productions as p', 'p.id', '=', 'r.production_id')
            ->leftJoin('companies as c', 'c.id', '=', 'p.company_id')
            ->whereIn('r.id', $baseIds)
            ->selectRaw('COALESCE(c.name, "Sem empresa") as name, COUNT(DISTINCT r.id) as total')
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $rows->pluck('name')->toArray(),
                'datasets' => [[
                    'label' => 'Retornos',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                    'backgroundColor' => 'rgba(15,118,110,.3)',
                    'borderColor' => '#0f766e',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Empresas executoras (top 8)'],
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Quantidade'],
                    ],
                ],
            ],
        ];
    }

    protected function buildProductionStatusChart(): array
    {
        $baseIds = $this->baseIdSubquery();

        $rows = DB::table('reclaims as r')
            ->leftJoin('productions as p', 'p.id', '=', 'r.production_id')
            ->whereIn('r.id', $baseIds)
            ->whereNotNull('p.status')
            ->selectRaw('p.status as status, COUNT(DISTINCT r.id) as total')
            ->groupBy('p.status')
            ->orderByDesc('total')
            ->get();

        $labels = $rows->map(function ($row) {
            $status = Notestatus::status((int) $row->status);
            return $status->status ?? (string) $row->status;
        })->toArray();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Status de producao',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                    'backgroundColor' => 'rgba(59,130,246,.3)',
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Status das producoes vinculadas'],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Quantidade'],
                    ],
                ],
            ],
        ];
    }

    protected function buildServiceChart(): array
    {
        $baseIds = $this->baseIdSubquery();

        $rows = DB::table('reclaims as r')
            ->leftJoin('services as s', 's.uuid', '=', 'r.service_id')
            ->whereIn('r.id', $baseIds)
            ->selectRaw('COALESCE(s.service, "Sem servico") as service, COUNT(DISTINCT r.id) as total')
            ->groupBy('service')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $rows->pluck('service')->toArray(),
                'datasets' => [[
                    'label' => 'Retornos',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                    'backgroundColor' => 'rgba(245,158,11,.3)',
                    'borderColor' => '#f59e0b',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Servicos com mais retornos'],
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Quantidade'],
                    ],
                ],
            ],
        ];
    }

    protected function dispatchCharts(array $charts): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-ri_origem', $charts['origin']);
        $this->dispatchBrowserEvent('grafico-atualizar-ri_diario', $charts['daily']);
        $this->dispatchBrowserEvent('grafico-atualizar-ri_empresas', $charts['companies']);
        $this->dispatchBrowserEvent('grafico-atualizar-ri_status', $charts['status']);
        $this->dispatchBrowserEvent('grafico-atualizar-ri_servicos', $charts['services']);
    }

    public function render()
    {
        $summary = $this->buildSummary();
        $charts = $this->buildCharts();

        $this->dispatchCharts($charts);

        return view('livewire.reports.return-intern-dashboard', [
            'summary' => $summary,
            'originChart' => $charts['origin'],
            'dailyChart' => $charts['daily'],
            'companiesChart' => $charts['companies'],
            'statusChart' => $charts['status'],
            'servicesChart' => $charts['services'],
        ]);
    }

    protected function buildCharts(): array
    {
        return [
            'origin' => $this->buildOriginChart(),
            'daily' => $this->buildDailyChart(),
            'companies' => $this->buildTopCompaniesChart(),
            'status' => $this->buildProductionStatusChart(),
            'services' => $this->buildServiceChart(),
        ];
    }
}
