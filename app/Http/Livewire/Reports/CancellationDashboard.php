<?php

namespace App\Http\Livewire\Reports;

use App\Enum\CancellationRequestScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CancellationDashboard extends Component
{
    public string $dt_in = '';
    public string $dt_out = '';
    public string $categoryId = '';
    public string $scope = '';
    public string $status = '';
    public string $visibilityMode = 'HIERARCHY';
    public array $requesterIds = [];

    protected $queryString = [
        'dt_in' => ['except' => '', 'as' => 'de'],
        'dt_out' => ['except' => '', 'as' => 'ate'],
        'categoryId' => ['except' => '', 'as' => 'cat'],
        'scope' => ['except' => '', 'as' => 'tipo'],
        'status' => ['except' => '', 'as' => 'sts'],
        'visibilityMode' => ['except' => 'HIERARCHY', 'as' => 'vis'],
    ];

    public function mount(): void
    {
        if ($this->dt_in === '' || $this->dt_out === '') {
            $this->dt_out = now()->toDateString();
            $this->dt_in = now()->subDays(29)->toDateString();
        }

        if ((Auth::user()?->superadm || Auth::user()?->management) && !request()->has('vis')) {
            $this->visibilityMode = 'ALL';
        }
    }

    protected function visibleRequesterIds(): ?array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($this->visibilityMode === 'ALL') {
            return null;
        }

        if ($this->visibilityMode === 'SUCCESSION') {
            return $user->descendantsQuery(
                includeSelf: true,
                includeDelegations: false,
                includeDelegatesTreesForPrincipal: true
            )->pluck('users.id')->unique()->values()->all();
        }

        return $user->descendantsQuery(
            includeSelf: true,
            includeDelegations: true,
            includeDelegatesTreesForPrincipal: false
        )->pluck('users.id')->unique()->values()->all();
    }

    protected function selectedRequesterIds(): array
    {
        return collect($this->requesterIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    protected function getDateRange(): array
    {
        try {
            $start = Carbon::parse($this->dt_in)->startOfDay();
        } catch (\Throwable $e) {
            $start = now()->subDays(29)->startOfDay();
            $this->dt_in = $start->toDateString();
        }

        try {
            $end = Carbon::parse($this->dt_out)->endOfDay();
        } catch (\Throwable $e) {
            $end = now()->endOfDay();
            $this->dt_out = $end->toDateString();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            $this->dt_in = $start->toDateString();
            $this->dt_out = $end->toDateString();
        }

        return [$start, $end];
    }

    protected function baseQuery()
    {
        [$start, $end] = $this->getDateRange();
        $visibleRequesterIds = $this->visibleRequesterIds();
        $selectedRequesterIds = $this->selectedRequesterIds();

        return DB::table('cancellation_requests as cr')
            ->whereBetween(DB::raw('COALESCE(cr.submitted_at, cr.created_at)'), [$start, $end])
            ->when($visibleRequesterIds !== null, fn ($q) => $q->whereIn('cr.requested_by', $visibleRequesterIds))
            ->when(count($selectedRequesterIds), fn ($q) => $q->whereIn('cr.requested_by', $selectedRequesterIds))
            ->when($this->categoryId !== '', fn ($q) => $q->where('cr.category_id', (int) $this->categoryId))
            ->when($this->scope !== '', fn ($q) => $q->where('cr.scope', $this->scope))
            ->when($this->status !== '', fn ($q) => $q->where('cr.status', $this->status));
    }

    protected function buildSummary(): array
    {
        $base = $this->baseQuery();

        $totalDemand = (clone $base)->count();
        $closed = (clone $base)->whereIn('cr.status', ['DONE', 'REJECTED', 'ABORTED'])->count();
        $engineerPending = (clone $base)
            ->where('cr.requires_engineer_approval', true)
            ->where('cr.engineer_approval_status', 'PENDING')
            ->count();
        $finalized = (clone $base)->where('cr.status', 'DONE')->count();

        $topRequester = (clone $base)
            ->leftJoin('users as requester', 'requester.id', '=', 'cr.requested_by')
            ->selectRaw('COALESCE(requester.name, "Sem solicitante") as requester_name, COUNT(*) as total')
            ->groupBy('requester_name')
            ->orderByDesc('total')
            ->first();

        $avgExecution = (clone $base)
            ->whereNotNull('cr.assigned_at')
            ->whereNotNull('cr.closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, cr.assigned_at, cr.closed_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgClosure = (clone $base)
            ->whereNotNull('cr.submitted_at')
            ->whereNotNull('cr.closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, cr.submitted_at, cr.closed_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgEngineerApproval = (clone $base)
            ->whereNotNull('cr.engineer_approval_requested_at')
            ->whereNotNull('cr.engineer_approval_decided_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, cr.engineer_approval_requested_at, cr.engineer_approval_decided_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgFinalization = (clone $base)
            ->whereNotNull('cr.engineer_approval_decided_at')
            ->whereNotNull('cr.closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, cr.engineer_approval_decided_at, cr.closed_at)) as avg_seconds')
            ->value('avg_seconds');

        return [
            'total_demand' => (int) $totalDemand,
            'closed' => (int) $closed,
            'engineer_pending' => (int) $engineerPending,
            'finalized' => (int) $finalized,
            'principal_requester' => $topRequester->requester_name ?? '-',
            'principal_requester_total' => (int) ($topRequester->total ?? 0),
            'avg_execution_human' => $this->secondsToHuman((int) $avgExecution),
            'avg_closure_human' => $this->secondsToHuman((int) $avgClosure),
            'avg_engineer_approval_human' => $this->secondsToHuman((int) $avgEngineerApproval),
            'avg_finalization_human' => $this->secondsToHuman((int) $avgFinalization),
        ];
    }

    protected function buildTypeChart(): array
    {
        $rows = $this->baseQuery()
            ->selectRaw('cr.scope, COUNT(*) as total')
            ->groupBy('cr.scope')
            ->orderByDesc('total')
            ->get();

        $labels = $rows->map(function ($row) {
            return CancellationRequestScope::tryFrom((string) $row->scope)?->label() ?? (string) $row->scope;
        })->toArray();

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Classificacao por tipo',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                    'backgroundColor' => [
                        'rgba(15,118,110,.38)',
                        'rgba(37,99,235,.38)',
                        'rgba(245,158,11,.38)',
                        'rgba(239,68,68,.38)',
                    ],
                    'borderColor' => ['#0f766e', '#2563eb', '#f59e0b', '#ef4444'],
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'right'],
                    'title' => ['display' => true, 'text' => 'Classificacao por tipo de cancelamento'],
                ],
                'cutout' => '58%',
            ],
        ];
    }

    protected function buildDailyDemandChart(): array
    {
        [$start, $end] = $this->getDateRange();

        $rows = $this->baseQuery()
            ->selectRaw('DATE(COALESCE(cr.submitted_at, cr.created_at)) as day_ref, COUNT(*) as total')
            ->groupBy('day_ref')
            ->pluck('total', 'day_ref');

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
                    'label' => 'Quantidade de demanda',
                    'data' => $series,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37,99,235,.2)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.25,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title' => ['display' => true, 'text' => 'Demanda diaria de cancelamentos'],
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

    protected function buildCategoryChart(): array
    {
        $rows = $this->baseQuery()
            ->leftJoin('cancellation_categories as cc', 'cc.id', '=', 'cr.category_id')
            ->selectRaw('COALESCE(cc.name, "Sem categoria") as category_name, COUNT(*) as total')
            ->groupBy('category_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $rows->pluck('category_name')->toArray(),
                'datasets' => [[
                    'label' => 'Demandas',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                    'backgroundColor' => 'rgba(15,118,110,.32)',
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
                    'title' => ['display' => true, 'text' => 'Classificacao por categoria (top 8)'],
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

    protected function buildStatusChart(): array
    {
        $rows = $this->baseQuery()
            ->selectRaw('cr.status, COUNT(*) as total')
            ->groupBy('cr.status')
            ->orderByDesc('total')
            ->get();

        $labels = $rows->map(function ($row) {
            return match ((string) $row->status) {
                'DRAFT' => 'Rascunho',
                'SUBMITTED' => 'Enviado',
                'ASSIGNED' => 'Em execucao',
                'PAUSED' => 'Pausado',
                'DONE' => 'Concluido',
                'REJECTED' => 'Rejeitado',
                'ABORTED' => 'Abortado',
                default => (string) $row->status,
            };
        })->toArray();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Quantidade',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                    'backgroundColor' => 'rgba(245,158,11,.3)',
                    'borderColor' => '#f59e0b',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Status das solicitacoes'],
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

    protected function buildTopRequesters()
    {
        return $this->baseQuery()
            ->leftJoin('users as requester', 'requester.id', '=', 'cr.requested_by')
            ->selectRaw('COALESCE(requester.name, "Sem solicitante") as requester_name, COUNT(*) as total')
            ->groupBy('requester_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    protected function buildTopExecutors()
    {
        return $this->baseQuery()
            ->leftJoin('users as assignee', 'assignee.id', '=', 'cr.assigned_to')
            ->selectRaw('COALESCE(assignee.name, "Sem executante") as executor_name, COUNT(*) as total')
            ->groupBy('executor_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    protected function buildCharts(): array
    {
        return [
            'type' => $this->buildTypeChart(),
            'daily' => $this->buildDailyDemandChart(),
            'category' => $this->buildCategoryChart(),
            'status' => $this->buildStatusChart(),
        ];
    }

    protected function dispatchCharts(array $charts): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-cxl_tipo', $charts['type']);
        $this->dispatchBrowserEvent('grafico-atualizar-cxl_diario', $charts['daily']);
        $this->dispatchBrowserEvent('grafico-atualizar-cxl_categoria', $charts['category']);
        $this->dispatchBrowserEvent('grafico-atualizar-cxl_status', $charts['status']);
    }

    protected function secondsToHuman(int $seconds): string
    {
        if ($seconds <= 0) {
            return '-';
        }

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        $chunks = [];
        if ($days > 0) {
            $chunks[] = $days . 'd';
        }
        if ($hours > 0) {
            $chunks[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $chunks[] = $minutes . 'min';
        }

        return empty($chunks) ? '< 1min' : implode(' ', $chunks);
    }

    public function render()
    {
        $summary = $this->buildSummary();
        $charts = $this->buildCharts();
        $topRequesters = $this->buildTopRequesters();
        $topExecutors = $this->buildTopExecutors();
        $topExecutor = $topExecutors->first();
        $categories = DB::table('cancellation_categories')
            ->orderBy('name')
            ->pluck('name', 'id');
        $visibleRequesterIds = $this->visibleRequesterIds();
        $requesterOptions = DB::table('users as u')
            ->join('cancellation_requests as cr', 'cr.requested_by', '=', 'u.id')
            ->when($visibleRequesterIds !== null, fn ($q) => $q->whereIn('u.id', $visibleRequesterIds))
            ->select('u.id', 'u.name')
            ->distinct()
            ->orderByRaw('LOWER(u.name)')
            ->get();

        $this->dispatchCharts($charts);

        return view('livewire.reports.cancellation-dashboard', [
            'summary' => $summary,
            'typeChart' => $charts['type'],
            'dailyChart' => $charts['daily'],
            'categoryChart' => $charts['category'],
            'statusChart' => $charts['status'],
            'topRequesters' => $topRequesters,
            'topExecutors' => $topExecutors,
            'principalExecutor' => $topExecutor->executor_name ?? '-',
            'principalExecutorTotal' => (int) ($topExecutor->total ?? 0),
            'categories' => $categories,
            'requesterOptions' => $requesterOptions,
            'visibilityOptions' => [
                ['value' => 'ALL', 'label' => 'Tudo'],
                ['value' => 'HIERARCHY', 'label' => 'Minha hierarquia'],
                ['value' => 'SUCCESSION', 'label' => 'Linha de sucessão'],
            ],
        ]);
    }
}
