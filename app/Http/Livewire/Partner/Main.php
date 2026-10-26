<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Partner\DashboardSummaryExport;
use App\Models\{Adsform, FiveNote, Partial, Reclaim, ReturnWork, Viability, WorkReport};
use App\Services\Ads\AdsDeadlinePolicy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\{Collection, Str};
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Main extends Component
{
    use \App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;

    public $pizza1;

    public $pizza2;

    public $backlogChart;

    public $dailyViabilityChart;

    public $viabilityAgeChart;

    public $d5AgeChart;

    public $rejectedWorkReasonChart;

    public $month;

    public $dt_ini;

    public $dt_fim;

    public int $daysAhead = 3;

    public array $kpis = [];

    public array $workReportKpis = [];

    public array $companyBreakdown = [];

    public array $executive = [];

    public $dadospizza1 = [
        'labels' => ['A', 'B', 'C'],
        'data'   => [10, 20, 70],
    ];

    public $dadospizza2 = [
        'labels' => ['A', 'B', 'C'],
        'data'   => [10, 20, 70],
    ];

    public $dadosBacklog = [
        'labels' => ['A', 'B', 'C'],
        'data'   => [10, 20, 70],
    ];

    public $dadosDailyViability = [
        'labels' => ['A', 'B', 'C'],
        'data'   => [10, 20, 70],
    ];

    public $dadosViabilityAgeHistogram = [
        'labels'  => [],
        'data'    => [],
        'total'   => 0,
        'oldest'  => 0,
        'average' => 0,
    ];

    public $dadosD5AgeHistogram = [
        'labels'  => [],
        'data'    => [],
        'total'   => 0,
        'oldest'  => 0,
        'average' => 0,
    ];

    public $dadosRejectedWorkReasons = [
        'labels' => [],
        'data'   => [],
        'total'  => 0,
    ];

    protected array $overdueCache = [];

    public function mount()
    {
        $this->pizza1                  = 'chart-' . Str::random(8);
        $this->pizza2                  = 'chart-' . Str::random(8);
        $this->backlogChart            = 'chart-' . Str::random(8);
        $this->dailyViabilityChart     = 'chart-' . Str::random(8);
        $this->viabilityAgeChart       = 'chart-' . Str::random(8);
        $this->d5AgeChart              = 'chart-' . Str::random(8);
        $this->rejectedWorkReasonChart = 'chart-' . Str::random(8);

        $this->month  = now()->format('Y-m');
        $this->dt_ini = now()->startOfMonth()->format('Y-m-d');
        $this->dt_fim = now()->endOfMonth()->format('Y-m-d');

        $this->toUpdateGraphs();
    }

    public function toUpdateGraphs()
    {
        $this->kpis           = $this->getDashboardKpis();
        $this->workReportKpis = $this->getWorkReportPeriodKpis();
        $this->atualizaViabilityAgeHistogram();
        $this->atualizaD5AgeHistogram();
        $this->atualizaRejectedWorkReasonChart();
        $this->executive        = $this->buildExecutiveData();
        $this->companyBreakdown = $this->buildCompanyBreakdown();
    }

    public function updatedPartnerCompanyFilter(): void
    {
        $this->toUpdateGraphs();
    }

    /**
     * Dados do painel executivo: séries de 6 meses (valores de ADS final/parcial e informes),
     * aging do atraso de ADS e vencimentos dos próximos dias. Respeita o filtro de empresa.
     */
    protected function buildExecutiveData(): array
    {
        // Consolidado mensal do ano de referência (jan–dez); meses futuros ficam sem valor.
        $year   = Carbon::parse($this->dt_fim ?: now())->year;
        $months = collect(range(1, 12))->map(fn ($month) => Carbon::create($year, $month, 1));
        $end    = Carbon::create($year, 12, 31)->endOfDay();
        $from   = Carbon::create($year, 1, 1)->startOfDay();
        $keys   = $months->map(fn (Carbon $month) => $month->format('Y-m'));

        $finalByMonth = Adsform::query()
            ->join('work_reports', 'work_reports.id', '=', 'adsforms.work_report_id')
            ->whereNotNull('work_reports.informed_at')
            ->whereBetween('work_reports.informed_at', [$from, $end])
            ->where('work_reports.canceled', false)
            ->where('work_reports.rejected', false)
            ->where(function ($q) {
                $q->where('adsforms.tacit', false)
                    ->orWhereNull('adsforms.tacit')
                    ->orWhereNotNull('adsforms.tacit_delivered_at');
            });
        $this->scopeByCompany($finalByMonth, 'work_reports.company_id');
        $finalByMonth = $finalByMonth
            ->selectRaw("DATE_FORMAT(work_reports.informed_at, '%Y-%m') as ym, SUM(adsforms.amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $partialByMonth = Partial::query()
            ->whereBetween('created_at', [$from, $end])
            ->where('payment', true)
            ->where('deny', false);
        $this->scopeByCompany($partialByMonth);
        $partialByMonth = $partialByMonth
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(value) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $informsByMonth = WorkReport::query()
            ->whereNotNull('informed_at')
            ->whereBetween('informed_at', [$from, $end]);
        $this->scopeByCompany($informsByMonth);
        $informsByMonth = $informsByMonth
            ->selectRaw("DATE_FORMAT(informed_at, '%Y-%m') as ym, COUNT(*) as total, SUM(CASE WHEN canceled = 0 AND rejected = 0 THEN 1 ELSE 0 END) as valid")
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $aging = $this->getWorkReportsWithoutAdsOverdue()->map(fn ($workReport) => (int) $workReport->ads_late_days);

        $buckets = [
            ['label' => '1–3 dias', 'min' => 1, 'max' => 3],
            ['label' => '4–7 dias', 'min' => 4, 'max' => 7],
            ['label' => '8–15 dias', 'min' => 8, 'max' => 15],
            ['label' => '16–30 dias', 'min' => 16, 'max' => 30],
            ['label' => 'Acima de 30 dias', 'min' => 31, 'max' => PHP_INT_MAX],
        ];

        $dueAds  = $this->getWorkReportsWithoutAdsDueSoon()->groupBy('ads_days_left')->map->count();
        $dueViab = $this->getViabilityDueDate()->groupBy(
            fn ($viability) => Carbon::today()->diffInDays(Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays())->startOfDay(), false)
        )->map->count();

        return [
            'labels'          => $months->map(fn (Carbon $month) => mb_strtolower($month->translatedFormat('M')))->all(),
            'last_index'      => $year === now()->year ? now()->month - 1 : 11,
            'final_amount'    => $keys->map(fn ($key) => (float) ($finalByMonth[$key] ?? 0))->all(),
            'partial_amount'  => $keys->map(fn ($key) => (float) ($partialByMonth[$key] ?? 0))->all(),
            'informs_valid'   => $keys->map(fn ($key) => (int) ($informsByMonth[$key]->valid ?? 0))->all(),
            'informs_invalid' => $keys->map(fn ($key) => (int) (($informsByMonth[$key]->total ?? 0) - ($informsByMonth[$key]->valid ?? 0)))->all(),
            'aging'           => collect($buckets)->map(fn ($bucket) => [
                'label' => $bucket['label'],
                'total' => $aging->filter(fn ($age) => $age >= $bucket['min'] && $age <= $bucket['max'])->count(),
            ])->all(),
            'overdue_total' => $aging->count(),
            'overdue_old'   => $aging->filter(fn ($age) => $age > 90)->count(),
            'due_by_day'    => collect(range(0, $this->daysAhead))->map(fn ($day) => [
                'label'     => $day === 0 ? 'Hoje' : ($day === 1 ? 'Amanhã' : "Em {$day} dias"),
                'ads'       => (int) ($dueAds[$day] ?? 0),
                'viability' => (int) ($dueViab[$day] ?? 0),
            ])->all(),
        ];
    }

    /**
     * Visão por empresa/filial: mesmos KPIs do painel, calculados separadamente para cada empresa visível.
     * Só existe na visão consolidada (sem filtro de empresa) e para perfis com mais de uma empresa.
     */
    protected function buildCompanyBreakdown(): array
    {
        $user = auth()->user();

        if (!$user || $user->superadm || (string) ($this->partnerCompanyFilter ?? '') !== '') {
            return [];
        }

        $ids = \App\Services\PartnerAccess\PartnerAccessGate::visibleCompanyIdsFor($user);

        if (count($ids) < 2) {
            return [];
        }

        $companies = \App\Models\Company::withTrashed()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'parent_id'])
            ->sortBy('name');

        $original = $this->partnerCompanyFilter;
        $rows     = [];

        try {
            foreach ($companies as $company) {
                $this->partnerCompanyFilter = (string) $company->id;

                $rows[] = [
                    'id'        => (string) $company->id,
                    'name'      => $company->name,
                    'is_branch' => (bool) $company->parent_id,
                    'kpis'      => $this->getDashboardKpis(),
                    'period'    => $this->getWorkReportPeriodKpis(),
                ];
            }
        } finally {
            $this->partnerCompanyFilter = $original;
        }

        return $rows;
    }

    public function updatedMonth()
    {
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        $this->toUpdateGraphs();
    }

    public function updatedDtIni()
    {
        $this->month = Carbon::parse($this->dt_ini)->format('Y-m');

        $this->toUpdateGraphs();
    }

    public function updatedDtFim()
    {
        $this->toUpdateGraphs();
    }

    protected function companyIds(): array
    {
        $user              = auth()->user();
        $companyIds        = \App\Services\PartnerAccess\PartnerAccessGate::visibleCompanyIdsFor($user);
        $selectedCompanyId = (string) ($this->partnerCompanyFilter ?? '');

        if ($selectedCompanyId !== '' && (empty($companyIds) || in_array($selectedCompanyId, $companyIds, true))) {
            return [$selectedCompanyId];
        }

        return $companyIds;
    }

    protected function scopeByCompany(Builder $query, string $column = 'company_id'): Builder
    {
        return $this->applyPartnerCompanyScope($query, $column);
    }

    public function getViabilityDueDate(): Collection
    {
        $today    = Carbon::today();
        $dueLimit = Carbon::today()->addDays($this->daysAhead);

        $query = Viability::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('completed', false)
            ->where('canceled', false)
            ->whereNotNull('sended_at')
            ->with(['Note:id,note', 'Company:id,name']);

        $this->scopeByCompany($query);

        return $query->get()
            ->filter(function ($viability) use ($today, $dueLimit) {
                $dueDate = Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays());

                return $dueDate->betweenIncluded($today, $dueLimit);
            })
            ->sortBy(function ($viability) {
                return Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays())->timestamp;
            })
            ->values();
    }

    /** Informes sem ADS tácita com prazo (work_reports.ads_due_at) vencendo entre hoje e os próximos dias. */
    public function getWorkReportsWithoutAdsDueSoon(): Collection
    {
        $today = Carbon::today();

        $query = WorkReport::query()->active()
            ->where('rejected', false)
            ->whereBetween('ads_due_at', [$today->copy()->startOfDay(), $today->copy()->addDays($this->daysAhead)->endOfDay()])
            ->whereDoesntHave('Adsform', function ($q) {
                // Só conta como entregue a ADS vinculada ao informe; tácita sem entrega não conta.
                $q->where(fn ($q) => $q->where('tacit', false)->orWhereNull('tacit')->orWhereNotNull('tacit_delivered_at'));
            })
            ->whereDoesntHave('Note.OldAds')
            ->with(['Note:id,note', 'Company:id,name', 'Company.Address'])
            ->orderBy('ads_due_at');

        $this->scopeByCompany($query);

        return $query->get()
            ->each(function (WorkReport $workReport) use ($today) {
                $workReport->ads_days_left = $today->diffInDays($workReport->ads_due_at->copy()->startOfDay(), false);
            })
            ->values();
    }

    /**
     * Informes ativos sem ADS cujo prazo de entrega (AdsDeadlinePolicy: dias úteis ou corridos conforme
     * SicodeRules) já venceu. Cada item ganha ads_due_at e ads_late_days.
     */
    public function getWorkReportsWithoutAdsOverdue(): Collection
    {
        // Chamado por KPIs e painel executivo na mesma requisição: calcula uma vez (propriedade protected não é serializada).
        return $this->overdueCache[(string) $this->partnerCompanyFilter] ??= $this->buildWorkReportsWithoutAdsOverdue();
    }

    /** Atraso de ADS = ADS tácita vencida (tacit_due_at) sem tacit_delivered_at; os dias contam a partir de tacit_due_at. */
    protected function buildWorkReportsWithoutAdsOverdue(): Collection
    {
        return $this->getTacitAdsOverdueWithoutDelivery()
            ->filter(fn (WorkReport $workReport) => $workReport->ads_late_days >= 1)
            ->values();
    }

    public function getTacitAdsOverdueWithoutDelivery(): Collection
    {
        $query = WorkReport::query()->active()
            ->where('rejected', false)
            ->whereHas('Adsform', function ($q) {
                $q->where('tacit', true)
                    ->whereNull('tacit_delivered_at')
                    ->whereNotNull('tacit_due_at')
                    ->where('tacit_due_at', '<', now());
            })
            ->join('adsforms', 'adsforms.work_report_id', '=', 'work_reports.id')
            ->with([
                'Note:id,note',
                'Adsform:id,work_report_id,tacit_due_at,tacit_delivered_at',
                'Company.Address',
            ])
            ->orderBy('adsforms.tacit_due_at')
            ->select('work_reports.*');

        $this->scopeByCompany($query);

        $deadlinePolicy = app(AdsDeadlinePolicy::class);

        return $query->get()
            ->map(function (WorkReport $workReport) use ($deadlinePolicy) {
                $state                     = $deadlinePolicy->stateForWorkReport($workReport);
                $workReport->ads_late_days = $deadlinePolicy->lateDays(
                    $workReport->Adsform?->tacit_due_at,
                    $workReport->Adsform?->tacit_delivered_at,
                    now(),
                    $state,
                    (int) $workReport->id
                );

                return $workReport;
            });
    }

    public function getDashboardKpis(): array
    {
        $baseViability = Viability::query()->where('canceled', false);
        $this->scopeByCompany($baseViability);

        $baseWorkReport = WorkReport::query()->active();
        $this->scopeByCompany($baseWorkReport);

        $rejectedViabilityBase = Viability::query()
            ->where('rejected', true)
            ->where('completed', false)
            ->where('status', 5);
        $this->scopeByCompany($rejectedViabilityBase);

        $d5PendingBase = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false)
            ->where('returned', false);
        $this->scopeByCompany($d5PendingBase);

        $d5ReturnedBase = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false)
            ->where('returned', true);
        $this->scopeByCompany($d5ReturnedBase);

        $reclaimsBase = Reclaim::query()
            ->where('completed', false)
            ->whereHas('Viabilities', function ($q) {
                $this->scopeByCompany($q);
            });

        return [
            'pending_viability' => (clone $baseViability)
                ->where('completed', false)
                ->where('status', 1)
                ->count(),
            'viability_due_soon'         => $this->getViabilityDueDate()->count(),
            'work_without_ads_due_soon'  => $this->getWorkReportsWithoutAdsDueSoon()->count(),
            'work_without_ads_overdue'   => $this->getWorkReportsWithoutAdsOverdue()->count(),
            'd5_pending'                 => $d5PendingBase->count(),
            'd5_returned'                => $d5ReturnedBase->count(),
            'viability_rejected_waiting' => $rejectedViabilityBase->count(),
            'reclaims_pending'           => $reclaimsBase->distinct('reclaims.id')->count('reclaims.id'),
            'informs_rejected'           => (clone $baseWorkReport)->where('rejected', true)->count(),
        ];
    }

    public function getWorkReportPeriodKpis(): array
    {
        $baseQuery = WorkReport::query()
            ->whereNotNull('informed_at');

        if (!empty($this->dt_ini)) {
            $baseQuery->whereDate('informed_at', '>=', $this->dt_ini);
        }

        if (!empty($this->dt_fim)) {
            $baseQuery->whereDate('informed_at', '<=', $this->dt_fim);
        }

        $this->scopeByCompany($baseQuery);

        $validBaseQuery = (clone $baseQuery)
            ->where('canceled', false)
            ->where('rejected', false);

        $workReportIds      = (clone $baseQuery)->select('id');
        $validWorkReportIds = (clone $validBaseQuery)->select('id');
        $adsBaseQuery       = Adsform::query()
            ->whereIn('work_report_id', $validWorkReportIds)
            ->where(function ($q) {
                $q->where('tacit', false)
                    ->orWhereNull('tacit')
                    ->orWhereNotNull('tacit_delivered_at');
            });

        $partialBaseQuery = Partial::query();

        if (!empty($this->dt_ini)) {
            $partialBaseQuery->whereDate('created_at', '>=', $this->dt_ini);
        }

        if (!empty($this->dt_fim)) {
            $partialBaseQuery->whereDate('created_at', '<=', $this->dt_fim);
        }

        $this->scopeByCompany($partialBaseQuery);

        $validInformedTotal           = (clone $validBaseQuery)->count();
        $adsDeliveredTotal            = (clone $adsBaseQuery)->distinct('work_report_id')->count('work_report_id');
        $partialsTotal                = (clone $partialBaseQuery)->count();
        $paidNotRejectedPartialsQuery = (clone $partialBaseQuery)
            ->where('payment', true)
            ->where('deny', false);
        $partialsAmountTotal = (float) (clone $paidNotRejectedPartialsQuery)->sum('value');

        return [
            'informed_total'          => (clone $baseQuery)->count(),
            'valid_informed_total'    => $validInformedTotal,
            'canceled_rejected_total' => (clone $baseQuery)
                ->where(function ($q) {
                    $q->where('canceled', true)
                        ->orWhere('rejected', true);
                })
                ->count(),
            'ads_delivered_total'      => $adsDeliveredTotal,
            'ads_not_delivered_total'  => max(0, $validInformedTotal - $adsDeliveredTotal),
            'ads_amount_total'         => (float) (clone $adsBaseQuery)->sum('amount'),
            'partials_total'           => $partialsTotal,
            'partials_rejected_total'  => (clone $partialBaseQuery)->where('deny', true)->count(),
            'partials_completed_total' => (clone $partialBaseQuery)
                ->where('payment', true)
                ->where('deny', false)
                ->count(),
            'partials_amount_total' => $partialsAmountTotal,
        ];
    }

    public function getViabilityCounts()
    {
        $baseQuery = Viability::query()->where('canceled', false);

        if (!empty($this->dt_ini)) {
            $baseQuery->whereDate('sended_at', '>=', $this->dt_ini);
        }

        if (!empty($this->dt_fim)) {
            $baseQuery->whereDate('sended_at', '<=', $this->dt_fim);
        }

        $this->scopeByCompany($baseQuery);

        return [
            'inViability' => (clone $baseQuery)->where('approved', false)
                ->where('rejected', false)
                ->where('completed', false)
                ->where('tacit', false)
                ->count(),

            'realized' => (clone $baseQuery)->where(function ($q) {
                $q->where('approved', true)
                    ->orWhere('rejected', true);
            })
                ->where('tacit', false)
                ->count(),

            'notRealized' => (clone $baseQuery)->where('tacit', true)
                ->count(),
        ];
    }

    public function atualizarViabilityCounts()
    {
        $counts = $this->getViabilityCounts();

        $labels = ['Em Viabilidade', 'Realizadas', 'Não Realizadas'];
        $data   = [
            $counts['inViability'],
            $counts['realized'],
            $counts['notRealized'],
        ];

        $this->dadospizza1 = [
            'labels' => $labels,
            'data'   => $data,
        ];

        $this->updateDataPizza($this->pizza1, $labels, $data);
    }

    public function getReturnWorkReports()
    {
        $baseQuery = ReturnWork::query();

        $companyIds = $this->companyIds();

        if (!empty($companyIds)) {
            $baseQuery->whereRelation('Workreport', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        if ($this->dt_ini) {
            $baseQuery->whereDate('created_at', '>=', $this->dt_ini);
        }

        if ($this->dt_fim) {
            $baseQuery->whereDate('created_at', '<=', $this->dt_fim);
        }

        return $baseQuery->selectRaw('count(*) as total, category')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();
    }

    public function atualizaReturnWorkReports()
    {
        $returnWorkReports = $this->getReturnWorkReports();

        if ($returnWorkReports->isEmpty()) {
            $this->dadospizza2 = [
                'labels' => [],
                'data'   => [],
            ];

            $this->updateDataPizza($this->pizza2, [], []);

            return;
        }

        $labels = $returnWorkReports->pluck('category')->toArray();
        $data   = $returnWorkReports->pluck('total')->toArray();

        $this->dadospizza2 = [
            'labels' => $labels,
            'data'   => $data,
        ];

        $this->updateDataPizza($this->pizza2, $labels, $data);
    }

    public function atualizaRejectedWorkReasonChart(): void
    {
        $returnWorkReports = $this->getReturnWorkReports();

        $labels = $returnWorkReports
            ->map(fn ($item) => filled($item->category) ? $item->category : 'Sem motivo informado')
            ->toArray();
        $data = $returnWorkReports->pluck('total')->map(fn ($total) => (int) $total)->toArray();

        $this->dadosRejectedWorkReasons = [
            'labels' => $labels,
            'data'   => $data,
            'total'  => array_sum($data),
        ];

        $this->dispatchHorizontalBarChart(
            $this->rejectedWorkReasonChart,
            $labels,
            $data,
            'Informes rejeitados',
            '#dc2626'
        );
    }

    public function atualizaBacklogChart(): void
    {
        $kpis = $this->kpis ?: $this->getDashboardKpis();

        $labels = [
            'Viabilidade pendente',
            'Viabilidade a vencer',
            'Entregas de ADS a vencer',
            'D5 pendente',
            'Viabilidade rejeitada',
            'Reclamações pendentes',
            'Informes rejeitados',
        ];

        $data = [
            (int) ($kpis['pending_viability'] ?? 0),
            (int) ($kpis['viability_due_soon'] ?? 0),
            (int) ($kpis['work_without_ads_due_soon'] ?? 0),
            (int) ($kpis['d5_pending'] ?? 0),
            (int) ($kpis['viability_rejected_waiting'] ?? 0),
            (int) ($kpis['reclaims_pending'] ?? 0),
            (int) ($kpis['informs_rejected'] ?? 0),
        ];

        $this->dadosBacklog = [
            'labels' => $labels,
            'data'   => $data,
        ];

        $this->updateDataPizza($this->backlogChart, $labels, $data);
    }

    public function atualizaDailyViability(): void
    {
        $baseQuery = Viability::query()
            ->where('canceled', false)
            ->whereNotNull('sended_at');

        if (!empty($this->dt_ini)) {
            $baseQuery->whereDate('sended_at', '>=', $this->dt_ini);
        }

        if (!empty($this->dt_fim)) {
            $baseQuery->whereDate('sended_at', '<=', $this->dt_fim);
        }

        $this->scopeByCompany($baseQuery);

        $data = $baseQuery
            ->selectRaw('DATE(sended_at) as raw_date, DATE_FORMAT(MIN(sended_at), "%d/%m") as ref_date, COUNT(*) as total')
            ->groupBy('raw_date')
            ->orderBy('raw_date')
            ->get();

        $labels = $data->pluck('ref_date')->toArray();
        $series = $data->pluck('total')->toArray();

        $this->dadosDailyViability = [
            'labels' => $labels,
            'data'   => $series,
        ];

        $this->updateDataPizza($this->dailyViabilityChart, $labels, $series);
    }

    public function atualizaViabilityAgeHistogram(): void
    {
        $histogram = $this->getOpenViabilityAgeHistogram();

        $this->dadosViabilityAgeHistogram = $histogram;
        $this->dispatchBarChart(
            $this->viabilityAgeChart,
            $histogram['labels'],
            $histogram['data'],
            'Viabilidades em aberto',
            '#0f766e',
            'Dias desde o envio'
        );
    }

    public function getOpenViabilityAgeHistogram(): array
    {
        $query = Viability::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('completed', false)
            ->where('canceled', false)
            ->whereNotNull('sended_at');

        $this->scopeByCompany($query);

        $buckets = array_fill(0, 22, 0);
        $ages    = [];

        $query->get(['sended_at'])->each(function ($viability) use (&$buckets, &$ages) {
            $age    = max(0, Carbon::parse($viability->sended_at)->startOfDay()->diffInDays(now()->startOfDay(), false));
            $bucket = min($age, 21);

            $buckets[$bucket]++;
            $ages[] = $age;
        });

        $labels   = array_map(fn ($day) => (string) $day, range(0, 20));
        $labels[] = '21+';

        return [
            'labels'  => $labels,
            'data'    => array_values($buckets),
            'total'   => count($ages),
            'oldest'  => empty($ages) ? 0 : max($ages),
            'average' => empty($ages) ? 0 : round(array_sum($ages) / count($ages), 1),
        ];
    }

    public function atualizaD5AgeHistogram(): void
    {
        $histogram = $this->getOpenD5AgeHistogram();

        $this->dadosD5AgeHistogram = $histogram;
        $this->dispatchD5StackedChart($this->d5AgeChart, $histogram);
    }

    public function getOpenD5AgeHistogram(): array
    {
        $query = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false)
            ->whereNotNull('dispatch_at');

        $this->scopeByCompany($query);

        $waitingBuckets  = array_fill(0, 31, 0);
        $rejectedBuckets = array_fill(0, 31, 0);
        $ages            = [];
        $passiveTotal    = 0;

        $query->get(['dispatch_at', 'returned', 'isPassive'])->each(function ($fiveNote) use (&$waitingBuckets, &$rejectedBuckets, &$ages, &$passiveTotal) {
            $age    = max(0, Carbon::parse($fiveNote->dispatch_at)->startOfDay()->diffInDays(now()->startOfDay(), false));
            $bucket = min($age, 30);

            if ($fiveNote->isPassive) {
                $passiveTotal++;
            }

            if ($fiveNote->returned) {
                $rejectedBuckets[$bucket]++;
            } else {
                $waitingBuckets[$bucket]++;
            }

            $ages[] = $age;
        });

        $labels       = array_map(fn ($day) => (string) $day, range(0, 29));
        $labels[]     = '30+';
        $waitingData  = array_values($waitingBuckets);
        $rejectedData = array_values($rejectedBuckets);

        return [
            'labels'        => $labels,
            'data'          => $waitingData,
            'rejected_data' => $rejectedData,
            'datasets'      => [
                [
                    'label'           => 'Em espera',
                    'data'            => $waitingData,
                    'backgroundColor' => '#2563eb',
                    'borderColor'     => '#2563eb',
                    'borderRadius'    => 6,
                    'maxBarThickness' => 34,
                    'stack'           => 'd5',
                ],
                [
                    'label'           => 'Rejeitados',
                    'data'            => $rejectedData,
                    'backgroundColor' => '#dc2626',
                    'borderColor'     => '#dc2626',
                    'borderRadius'    => 6,
                    'maxBarThickness' => 34,
                    'stack'           => 'd5',
                ],
            ],
            'total'          => count($ages),
            'waiting_total'  => array_sum($waitingData),
            'rejected_total' => array_sum($rejectedData),
            'passive_total'  => $passiveTotal,
            'oldest'         => empty($ages) ? 0 : max($ages),
            'average'        => empty($ages) ? 0 : round(array_sum($ages) / count($ages), 1),
        ];
    }

    public function exportSummaryXlsx()
    {
        $kpis      = $this->kpis ?: $this->getDashboardKpis();
        $period    = $this->workReportKpis ?: $this->getWorkReportPeriodKpis();
        $executive = $this->executive ?: $this->buildExecutiveData();
        $pct       = fn ($part, $total) => $total > 0 ? round($part / $total * 100, 1) . '%' : '—';

        $company = (string) ($this->partnerCompanyFilter ?? '') !== ''
            ? (\App\Models\Company::withTrashed()->find($this->partnerCompanyFilter)?->name ?? 'Empresa selecionada')
            : (count($this->companyBreakdown) ? 'Total consolidado' : 'Todas as empresas visíveis');

        // Zero é "Em dia"; acima de zero é atenção (ou crítico, quando já gera multa).
        $status = fn (int $value, string $level = 'Atenção') => $value === 0 ? 'Em dia' : $level;

        $indicators = [
            ['Viabilidades pendentes de resposta', (int) ($kpis['pending_viability'] ?? 0), ''],
            ['Viabilidades a vencer em ' . $this->daysAhead . ' dias', (int) ($kpis['viability_due_soon'] ?? 0), $status((int) ($kpis['viability_due_soon'] ?? 0))],
            ['Entregas de ADS a vencer em ' . $this->daysAhead . ' dias', (int) ($kpis['work_without_ads_due_soon'] ?? 0), $status((int) ($kpis['work_without_ads_due_soon'] ?? 0))],
            ['Entregas de ADS em atraso', (int) ($kpis['work_without_ads_overdue'] ?? 0), $status((int) ($kpis['work_without_ads_overdue'] ?? 0), 'Crítico')],
            ['D5 pendentes', (int) ($kpis['d5_pending'] ?? 0), ''],
            ['D5 devolvidos', (int) ($kpis['d5_returned'] ?? 0), $status((int) ($kpis['d5_returned'] ?? 0))],
            ['Viabilidades rejeitadas aguardando resposta', (int) ($kpis['viability_rejected_waiting'] ?? 0), $status((int) ($kpis['viability_rejected_waiting'] ?? 0))],
            ['Reclamações pendentes', (int) ($kpis['reclaims_pending'] ?? 0), $status((int) ($kpis['reclaims_pending'] ?? 0))],
            ['Informes rejeitados', (int) ($kpis['informs_rejected'] ?? 0), $status((int) ($kpis['informs_rejected'] ?? 0))],
        ];

        $performance = [
            ['Informes concluídos no período', (int) ($period['informed_total'] ?? 0), '', ''],
            ['Informes válidos', (int) ($period['valid_informed_total'] ?? 0), '', ''],
            ['ADS entregues', (int) ($period['ads_delivered_total'] ?? 0), (int) ($period['valid_informed_total'] ?? 0), $pct($period['ads_delivered_total'] ?? 0, $period['valid_informed_total'] ?? 0)],
            ['Informes cancelados/rejeitados', (int) ($period['canceled_rejected_total'] ?? 0), (int) ($period['informed_total'] ?? 0), $pct($period['canceled_rejected_total'] ?? 0, $period['informed_total'] ?? 0)],
            ['Parciais finalizadas', (int) ($period['partials_completed_total'] ?? 0), (int) ($period['partials_total'] ?? 0), $pct($period['partials_completed_total'] ?? 0, $period['partials_total'] ?? 0)],
            ['Valor de ADS final solicitado', 'R$ ' . number_format((float) ($period['ads_amount_total'] ?? 0), 2, ',', '.'), '', ''],
            ['Valor de parciais pagas', 'R$ ' . number_format((float) ($period['partials_amount_total'] ?? 0), 2, ',', '.'), '', ''],
        ];

        $aging   = collect($executive['aging'] ?? [])->map(fn ($bucket) => [$bucket['label'], (int) $bucket['total']])->all();
        $aging[] = ['Total em atraso', (int) ($executive['overdue_total'] ?? 0)];

        $dueByDay = collect($executive['due_by_day'] ?? [])->map(fn ($day) => [
            $day['label'],
            (int) $day['ads'],
            (int) $day['viability'],
            (int) $day['ads'] + (int) $day['viability'],
        ])->all();

        $summary = [
            'headline' => 'Dashboard da Parceira — Resumo executivo',
            'context'  => [
                'Empresa'    => $company,
                'Período'    => Carbon::parse($this->dt_ini)->format('d/m/Y') . ' a ' . Carbon::parse($this->dt_fim)->format('d/m/Y'),
                'Gerado em'  => now()->format('d/m/Y H:i'),
                'Gerado por' => auth()->user()?->name ?? '',
            ],
            'sections' => [
                ['title' => 'Indicadores operacionais (situação atual)', 'headings' => ['Indicador', 'Quantidade', 'Situação'], 'rows' => $indicators, 'status' => 2],
                ['title' => 'Desempenho no período', 'headings' => ['Indicador', 'Resultado', 'Base', '%'], 'rows' => $performance, 'money' => [1]],
                ['title' => 'Atraso de ADS por tempo', 'headings' => ['Faixa de atraso', 'Quantidade'], 'rows' => $aging],
                ['title' => 'Vencimentos dos próximos dias', 'headings' => ['Prazo', 'ADS', 'Viabilidades', 'Total'], 'rows' => $dueByDay],
            ],
            'footnote' => 'Valores meramente informativos, calculados a partir das informações fornecidas pela parceira nas ADS dos informes e nos parciais válidos do período filtrado. Não constituem validação financeira, medição aprovada ou autorização para pagamento. Atraso de ADS conta a partir do vencimento do prazo tácito (' . \App\Support\SicodeRules::adsDeliveryDeadlineLabel() . ').',
        ];

        $overdueRows = $this->getWorkReportsWithoutAdsOverdue()->map(fn (WorkReport $report) => [
            $report->Note?->note ?? '',
            $report->Company?->name ?? '',
            optional($report->informed_at)->format('d/m/Y H:i'),
            optional($report->Adsform?->tacit_due_at)->format('d/m/Y H:i'),
            (int) $report->ads_late_days,
            $report->ads_late_days > 30 ? 'Acima de 30 dias' : ($report->ads_late_days > 15 ? '16–30 dias' : ($report->ads_late_days > 7 ? '8–15 dias' : ($report->ads_late_days > 3 ? '4–7 dias' : '1–3 dias'))),
        ])->sortByDesc(fn ($row) => $row[4])->values()->all();

        $dueRows = [];

        foreach ($this->getWorkReportsWithoutAdsDueSoon() as $report) {
            $dueRows[] = [
                'Entrega de ADS',
                $report->Note?->note ?? '',
                $report->Company?->name ?? '',
                optional($report->informed_at)->format('d/m/Y H:i'),
                optional($report->ads_due_at)->format('d/m/Y H:i'),
                (int) $report->ads_days_left,
            ];
        }

        foreach ($this->getViabilityDueDate() as $viability) {
            $dueDate   = Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays());
            $dueRows[] = [
                'Viabilidade',
                $viability->Note?->note ?? '',
                $viability->Company?->name ?? '',
                optional($viability->sended_at)->format('d/m/Y H:i'),
                $dueDate->format('d/m/Y H:i'),
                (int) Carbon::today()->diffInDays($dueDate->copy()->startOfDay(), false),
            ];
        }

        usort($dueRows, fn ($a, $b) => $a[5] <=> $b[5]);

        $months = collect($executive['labels'] ?? [])->map(fn ($label, $i) => [
            mb_convert_case($label, MB_CASE_TITLE),
            (float) ($executive['final_amount'][$i] ?? 0),
            (float) ($executive['partial_amount'][$i] ?? 0),
            (int) ($executive['informs_valid'][$i] ?? 0),
            (int) ($executive['informs_invalid'][$i] ?? 0),
        ])->all();

        $tables = [
            ['title' => 'Atraso de ADS', 'headings' => ['Nota', 'Empresa', 'Informado em', 'Prazo da ADS', 'Dias de atraso', 'Faixa'], 'rows' => $overdueRows],
            ['title' => 'A vencer', 'headings' => ['Tipo', 'Nota', 'Empresa', 'Recebido em', 'Vence em', 'Dias restantes'], 'rows' => $dueRows],
            ['title' => 'Evolução mensal', 'headings' => ['Mês', 'Valor ADS final (R$)', 'Valor ADS parcial (R$)', 'Informes válidos', 'Cancelados/rejeitados'], 'rows' => $months],
        ];

        if (!empty($this->companyBreakdown)) {
            $tables[] = [
                'title'    => 'Por empresa',
                'headings' => ['Empresa', 'Tipo', 'ADS em atraso', 'ADS a vencer', 'Viabilidades a vencer', 'D5 devolvidos', 'Informes rejeitados', 'Entrega de ADS (%)'],
                'rows'     => collect($this->companyBreakdown)->map(fn ($row) => [
                    $row['name'],
                    $row['is_branch'] ? 'Filial' : 'Matriz',
                    (int) ($row['kpis']['work_without_ads_overdue'] ?? 0),
                    (int) ($row['kpis']['work_without_ads_due_soon'] ?? 0),
                    (int) ($row['kpis']['viability_due_soon'] ?? 0),
                    (int) ($row['kpis']['d5_returned'] ?? 0),
                    (int) ($row['kpis']['informs_rejected'] ?? 0),
                    ($row['period']['valid_informed_total'] ?? 0) > 0
                        ? round(($row['period']['ads_delivered_total'] ?? 0) / $row['period']['valid_informed_total'] * 100, 1)
                        : '',
                ])->all(),
            ];
        }

        return Excel::download(
            new DashboardSummaryExport($summary, $tables),
            now()->format('YmdHis') . '-dashboard-parceira-resumo.xlsx'
        );
    }

    public function exportPendenciesCsv()
    {
        $fileName = now()->format('YmdHis') . '-partner-dashboard-pendencias.csv';

        $viabilityDueSoon      = $this->getViabilityDueDate();
        $workWithoutAdsDueSoon = $this->getWorkReportsWithoutAdsDueSoon();

        return response()->streamDownload(function () use ($viabilityDueSoon, $workWithoutAdsDueSoon) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Tipo', 'Nota', 'Empresa', 'Recebido em', 'Vence em', 'Dias restantes']);

            foreach ($viabilityDueSoon as $item) {
                $dueDate  = $item->sended_at?->copy()->addDays(7 + $item->getDays());
                $daysLeft = $dueDate
                    ? now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false)
                    : null;

                fputcsv($out, [
                    'Viabilidade',
                    $item->note->note ?? '',
                    $item->company->name ?? '',
                    optional($item->sended_at)->format('d/m/Y H:i'),
                    optional($dueDate)->format('d/m/Y H:i'),
                    $daysLeft,
                ]);
            }

            foreach ($workWithoutAdsDueSoon as $item) {
                $dueDate  = $item->ads_due_at;
                $daysLeft = $dueDate
                    ? now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false)
                    : null;

                fputcsv($out, [
                    'Entrega de ADS',
                    $item->note->note ?? '',
                    $item->company->name ?? '',
                    optional($item->informed_at)->format('d/m/Y H:i'),
                    optional($dueDate)->format('d/m/Y H:i'),
                    $daysLeft,
                ]);
            }

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function updateDataPizza(string $chartId = null, array $labels = [], array $data = [])
    {
        $this->dispatchBrowserEvent('updateGraph' . Str::studly($chartId), [
            'labels' => $labels,
            'data'   => $data,
        ]);

        $this->dispatchBarChart($chartId, $labels, $data);
    }

    private function dispatchBarChart(
        string $chartId = null,
        array $labels = [],
        array $data = [],
        string $label = 'Viabilidades em aberto',
        string $color = '#0f766e',
        string $xTitle = 'Dias desde o envio'
    ): void {
        $this->dispatchBrowserEvent('chart-update', [
            'chartId' => $chartId,
            'chart'   => [
                'type' => 'bar',
                'data' => [
                    'labels'   => $labels,
                    'datasets' => [[
                        'label'           => $label,
                        'data'            => $data,
                        'backgroundColor' => $color,
                        'borderColor'     => $color,
                        'borderRadius'    => 6,
                        'maxBarThickness' => 34,
                    ]],
                ],
                'options' => [
                    'plugins' => [
                        'legend'  => ['display' => false],
                        'tooltip' => [
                            'callbacks' => [
                                'label' => '__VALUE_LABEL__',
                            ],
                        ],
                    ],
                    'scales' => [
                        'x' => [
                            'title' => ['display' => true, 'text' => $xTitle],
                            'grid'  => ['display' => false],
                        ],
                        'y' => [
                            'beginAtZero' => true,
                            'title'       => ['display' => true, 'text' => 'Quantidade'],
                            'ticks'       => ['precision' => 0],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function dispatchD5StackedChart(string $chartId = null, array $histogram = []): void
    {
        $this->dispatchBrowserEvent('chart-update', [
            'chartId' => $chartId,
            'chart'   => [
                'type' => 'bar',
                'data' => [
                    'labels'   => $histogram['labels'] ?? [],
                    'datasets' => $histogram['datasets'] ?? [],
                ],
                'options' => [
                    'plugins' => [
                        'legend'  => ['display' => true, 'position' => 'top'],
                        'tooltip' => [
                            'callbacks' => [
                                'label' => '__VALUE_LABEL__',
                            ],
                        ],
                    ],
                    'scales' => [
                        'x' => [
                            'stacked' => true,
                            'title'   => ['display' => true, 'text' => 'Dias desde o despacho'],
                            'grid'    => ['display' => false],
                        ],
                        'y' => [
                            'stacked'     => true,
                            'beginAtZero' => true,
                            'title'       => ['display' => true, 'text' => 'Quantidade'],
                            'ticks'       => ['precision' => 0],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function dispatchHorizontalBarChart(
        string $chartId = null,
        array $labels = [],
        array $data = [],
        string $label = 'Registros',
        string $color = '#dc2626'
    ): void {
        $this->dispatchBrowserEvent('chart-update', [
            'chartId' => $chartId,
            'chart'   => [
                'type' => 'bar',
                'data' => [
                    'labels'   => $labels,
                    'datasets' => [[
                        'label'           => $label,
                        'data'            => $data,
                        'backgroundColor' => $color,
                        'borderColor'     => $color,
                        'borderRadius'    => 6,
                        'maxBarThickness' => 26,
                    ]],
                ],
                'options' => [
                    'indexAxis' => 'y',
                    'plugins'   => [
                        'legend'  => ['display' => false],
                        'tooltip' => [
                            'callbacks' => [
                                'label' => '__VALUE_LABEL__',
                            ],
                        ],
                    ],
                    'scales' => [
                        'x' => [
                            'beginAtZero' => true,
                            'title'       => ['display' => true, 'text' => 'Quantidade'],
                            'ticks'       => ['precision' => 0],
                        ],
                        'y' => [
                            'grid' => ['display' => false],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function getAdsTacitOverdueThreshold(): Carbon
    {
        return now()->subDays(3)->startOfDay();
    }

    public function render()
    {
        return view('livewire.partner.main', [
            'dueSoon'                        => $this->getViabilityDueDate(),
            'workReportsWithoutAdsDueSoon'   => $this->getWorkReportsWithoutAdsDueSoon(),
            'tacitAdsOverdueWithoutDelivery' => $this->getTacitAdsOverdueWithoutDelivery(),
            'openViabilityAgeHistogram'      => $this->dadosViabilityAgeHistogram,
            'openD5AgeHistogram'             => $this->dadosD5AgeHistogram,
            'rejectedWorkReasons'            => $this->dadosRejectedWorkReasons,
            'workReportKpis'                 => $this->workReportKpis,
        ]);
    }
}
