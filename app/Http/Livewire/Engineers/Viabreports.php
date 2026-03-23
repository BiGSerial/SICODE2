<?php

namespace App\Http\Livewire\Engineers;

use App\Models\Company;
use App\Models\Viability;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Viabreports extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $companies;

    // múltiplas empreiteiras selecionadas
    public $company_ids = [];

    // período atual da tela
    public $dt_in;
    public $dt_out;
    public string $export_by = 'note';
    public string $amount_basis = 'moa';

    public $perPage = 15;

    // mantido por compatibilidade, mas não vamos mais depender dele pro gráfico
    public $chartRenderKey = 0;

    protected $queryString = [
        'company_ids' => ['except' => []],
        'dt_in'       => ['except' => ''],
        'dt_out'      => ['except' => ''],
        'export_by'   => ['except' => 'note'],
        'amount_basis'=> ['except' => 'moa'],
    ];

    public function mount()
    {
        $this->companies = Company::has('Viabilies')
            ->when(!auth()->user()->superadm, function ($query) {
                $query->whereIn('id', auth()->user()->Companies->pluck('id'));
            })
            ->orderBy('name')
            ->get();

        // período padrão = mês atual
        $this->dt_in  = $this->dt_in ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dt_out = $this->dt_out ?: Carbon::now()->endOfMonth()->format('Y-m-d');

        // sincroniza front logo no primeiro load
        $this->refreshCharts();
    }

    /**
     * Helpers internos
     */
    protected function bumpCharts()
    {
        // reset da paginação quando filtro muda
        $this->resetPage();
    }

    /**
     * Força atualização visual dos gráficos no front
     * enviando dados calculados no PHP
     */
    protected function refreshCharts()
    {
        $this->dispatchBrowserEvent('grafico-atualizar-chartDaily', $this->chartDaily);
        $this->dispatchBrowserEvent('grafico-atualizar-chartMonthly', $this->chartMonthly);
        $this->dispatchBrowserEvent('grafico-atualizar-chartSLA', $this->chartSLA);
    }

    public function updatedDtIn()
    {
        $this->bumpCharts();
        $this->refreshCharts();
    }

    public function updatedDtOut()
    {
        $this->bumpCharts();
        $this->refreshCharts();
    }

    public function updatedCompanyIds()
    {
        $this->bumpCharts();
        $this->refreshCharts();
    }

    public function updatedExportBy()
    {
        if (!in_array($this->export_by, ['note', 'order'], true)) {
            $this->export_by = 'note';
        }
    }

    public function updatedAmountBasis()
    {
        if (!in_array($this->amount_basis, ['moa', 'mop'], true)) {
            $this->amount_basis = 'moa';
        }

        $this->bumpCharts();
        $this->refreshCharts();
    }

    /**
     * Botão "Limpar filtros"
     *
     * - volta para mês atual
     * - limpa seleção de empreiteiras (todas)
     */
    public function resetFilters()
    {
        $this->company_ids = [];
        $this->amount_basis = 'moa';
        $this->export_by = 'note';

        $this->dt_in  = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dt_out = Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->bumpCharts();
        $this->refreshCharts();
    }

    /**
     * Filtrar por empresas selecionadas (todas se vazio)
     */
    protected function applyCompanyFilter($query)
    {
        if (!empty($this->company_ids) && is_array($this->company_ids)) {
            $query->whereIn('viabilities.company_id', $this->company_ids);
        }

        return $query;
    }

    /**
     * close_date = COALESCE(returned_at, completed_at)
     */
    protected function closeDateExpr(): string
    {
        return "COALESCE(returned_at, completed_at)";
    }

    protected function predictedDateExpr(): string
    {
        return "TIMESTAMPADD(DAY, (7 + COALESCE(vdays.total_days, 0)), viabilities.sended_at)";
    }

    /**
     * Base: concluídas
     */
    protected function baseClosedQuery()
    {
        $q = Viability::query()
            ->where('completed', true);

        $this->applyCompanyFilter($q);
        $this->applyMonetaryJoin($q);

        return $q;
    }

    protected function applyMonetaryJoin($query)
    {
        if ($this->amount_basis !== 'mop') {
            return $query;
        }

        $query->leftJoinSub(
            DB::table('order_viability as ov')
                ->join('orders as o', 'o.id', '=', 'ov.order_id')
                ->selectRaw('ov.viability_id, SUM(COALESCE(o.service_cost, 0)) as total_service_cost')
                ->groupBy('ov.viability_id'),
            'mop_costs',
            function ($join) {
                $join->on('mop_costs.viability_id', '=', 'viabilities.id');
            }
        );

        return $query;
    }

    protected function monetaryAmountSql(): string
    {
        return $this->amount_basis === 'mop'
            ? 'COALESCE(mop_costs.total_service_cost, 0)'
            : 'COALESCE(viabilities.value, 0)';
    }

    protected function sumMonetary($query): float
    {
        if ($this->amount_basis === 'mop') {
            return round((float) $query->sum(DB::raw('COALESCE(mop_costs.total_service_cost, 0)')), 2);
        }

        return round((float) $query->sum('viabilities.value'), 2);
    }

    protected function amountBasisLabel(): string
    {
        return $this->amount_basis === 'mop'
            ? 'MOP - Mão de Obra Prevista'
            : 'MOA - Mão de Obra em Aberto';
    }

    /**
     * Realizado
     */
    protected function realizedClosedQuery()
    {
        $q = (clone $this->baseClosedQuery())
            ->where(function ($q2) {
                $q2->where('tacit', false)
                   ->orWhere(function ($sub) {
                       $sub->where('tacit', true)
                           ->whereHas('Justification', function ($j) {
                               $j->where('granted', true);
                           });
                   });
            });

        return $q;
    }

    /**
     * Não Realizado
     */
    protected function notRealizedClosedQuery()
    {
        $q = (clone $this->baseClosedQuery())
            ->where('tacit', true)
            ->where(function ($q2) {
                $q2->whereDoesntHave('Justification')
                   ->orWhereHas('Justification', function ($j) {
                       $j->where('dismissed', true);
                   });
            });

        return $q;
    }

    /**
     * Aplica range de datas (close_date no intervalo)
     */
    protected function applyCloseDateRange($query, $start, $end)
    {
        $query->whereBetween(
            DB::raw($this->closeDateExpr()),
            [$start, $end]
        );

        return $query;
    }

    /**
     * KPIs topo
     */
    public function getSummaryProperty(): array
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $realizedValueQ = (clone $this->realizedClosedQuery());
        $this->applyCloseDateRange($realizedValueQ, $start, $end);
        $realizedValue = $this->sumMonetary($realizedValueQ);

        $notRealizedValueQ = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($notRealizedValueQ, $start, $end);
        $notRealizedValue = $this->sumMonetary($notRealizedValueQ);

        $penaltyValue = $notRealizedValue * 0.01;

        // SLA médio (somente tacit = false com returned_at)
        $slaSet = (clone $this->baseClosedQuery())
            ->where('tacit', false)
            ->whereNotNull('returned_at');
        $this->applyCloseDateRange($slaSet, $start, $end);

        $slaSet = $slaSet->get(['sended_at', 'returned_at']);

        $totalHours = 0;
        $countSla   = 0;
        foreach ($slaSet as $row) {
            $s = Carbon::parse($row->sended_at);
            $r = Carbon::parse($row->returned_at);
            $totalHours += $s->diffInHours($r);
            $countSla++;
        }

        $avgHours = $countSla > 0 ? $totalHours / $countSla : 0;
        $avgDays  = $avgHours / 24;

        $periodLabel = $start->format('d/m') . ' - ' . $end->format('d/m');

        return [
            'periodLabel'        => $periodLabel,
            'realizedValue'      => $realizedValue,
            'notRealizedValue'   => $notRealizedValue,
            'penaltyValue'       => $penaltyValue,
            'avgCloseTimeHours'  => round($avgHours, 1),
            'avgCloseTimeDays'   => round($avgDays, 1),
            'amountBasisLabel'   => $this->amountBasisLabel(),
        ];
    }

    /**
     * Gráfico operacional (Backlog x Entrega + projeção)
     */
    public function getChartSLAProperty(): array
    {
        $endRef   = Carbon::now()->endOfMonth();
        $startRef = $endRef->copy()->subMonths(5)->startOfMonth();

        // lista meses reais
        $months = collect();
        $cursor = $startRef->copy();
        while ($cursor->lessThanOrEqualTo($endRef)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        // backlog inicial
        $backlogPrevQuery = Viability::query()
            ->where('sended_at', '<', $startRef)
            ->where(function ($q) use ($startRef) {
                $q->where('completed', false)
                  ->orWhere(function ($qq) use ($startRef) {
                      $qq->where('completed', true)
                         ->where(DB::raw('COALESCE(returned_at, completed_at)'), '>=', $startRef);
                  });
            });
        $this->applyCompanyFilter($backlogPrevQuery);
        $backlogPrev = $backlogPrevQuery->count();

        $labels                     = [];
        $dataFechadasMes            = [];
        $dataBacklogFinal           = [];
        $deltasEntradaMenosFechadas = [];

        foreach ($months as $monthStart) {
            $monthEnd = $monthStart->copy()->endOfMonth();

            // entradas no mês (sended_at)
            $entradasMesQuery = Viability::query()
                ->whereBetween('sended_at', [$monthStart, $monthEnd]);
            $this->applyCompanyFilter($entradasMesQuery);
            $entradasMes = $entradasMesQuery->count();

            // fechadas no mês (close_date)
            $fechadasMesQuery = Viability::query()
                ->where('completed', true)
                ->whereBetween(
                    DB::raw('COALESCE(returned_at, completed_at)'),
                    [$monthStart, $monthEnd]
                );
            $this->applyCompanyFilter($fechadasMesQuery);
            $fechadasMes = $fechadasMesQuery->count();

            $backlogPrev = $backlogPrev + $entradasMes - $fechadasMes;

            $labels[]                     = $monthStart->format('M/Y');
            $dataFechadasMes[]            = $fechadasMes;
            $dataBacklogFinal[]           = $backlogPrev;
            $deltasEntradaMenosFechadas[] = ($entradasMes - $fechadasMes);
        }

        // projeção (+1)
        $nextMonthStart = $endRef->copy()->addMonthNoOverflow()->startOfMonth();
        $labels[] = $nextMonthStart->format('M/Y') . ' (proj)';

        $lastDeltas = array_slice($deltasEntradaMenosFechadas, -3);
        $mediaDelta = count($lastDeltas) > 0
            ? array_sum($lastDeltas) / count($lastDeltas)
            : 0;

        $projBacklog = $backlogPrev + $mediaDelta;

        $dataFechadasMes[]  = 0;
        $dataBacklogFinal[] = max(0, (int) round($projBacklog));

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => 'Fechadas no mês (ativo)',
                        'data'            => $dataFechadasMes,
                        'backgroundColor' => 'rgba(40,167,69,0.3)',
                        'borderColor'     => '#28a745',
                        'borderWidth'     => 1,
                    ],
                    [
                        'type'            => 'bar',
                        'label'           => 'Backlog final (passivo)',
                        'data'            => $dataBacklogFinal,
                        'backgroundColor' => 'rgba(255,193,7,0.3)',
                        'borderColor'     => '#ffc107',
                        'borderWidth'     => 1,
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Backlog (passivo) x Entrega (ativo) • últimos 6 meses + previsão',
                    ],
                ],
                'scales' => [
                    'y' => [
                        'type'         => 'linear',
                        'display'      => true,
                        'position'     => 'left',
                        'title'        => ['display' => true, 'text' => 'Qtd de Viabilidades'],
                        'beginAtZero'  => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Gráfico diário por close_date
     */
    public function getChartDailyProperty(): array
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $realizadoDaily = Viability::query()
            ->whereNotNull('viabilities.returned_at')
            ->whereBetween('viabilities.returned_at', [$start, $end]);
        $this->applyCompanyFilter($realizadoDaily);
        $this->applyMonetaryJoin($realizadoDaily);

        $realizadoRows = $realizadoDaily
            ->selectRaw('
                DATE(viabilities.returned_at) as dia_ref,
                SUM(' . $this->monetaryAmountSql() . ') as valor_real,
                COUNT(*)   as qtd_real
            ')
            ->groupBy('dia_ref')
            ->get()
            ->keyBy('dia_ref');

        $naoDaily = Viability::query()
            ->where('viabilities.tacit', true)
            ->whereNotNull('viabilities.tacit_at')
            ->whereBetween('viabilities.tacit_at', [$start, $end]);
        $this->applyCompanyFilter($naoDaily);
        $this->applyMonetaryJoin($naoDaily);

        $naoRows = $naoDaily
            ->selectRaw('
                DATE(viabilities.tacit_at) as dia_ref,
                SUM(' . $this->monetaryAmountSql() . ') as valor_nao,
                COUNT(*)   as qtd_nao
            ')
            ->groupBy('dia_ref')
            ->get()
            ->keyBy('dia_ref');

        $previsaoDaily = Viability::query()
            ->leftJoinSub(
                DB::table('daysviabs')
                    ->selectRaw('viability_id, SUM(days) as total_days')
                    ->groupBy('viability_id'),
                'vdays',
                function ($join) {
                    $join->on('vdays.viability_id', '=', 'viabilities.id');
                }
            )
            ->whereNull('viabilities.returned_at')
            ->where('viabilities.canceled', false)
            ->where('viabilities.completed', false)
            ->where('viabilities.approved', false)
            ->where('viabilities.rejected', false)
            ->whereNotNull('viabilities.sended_at')
            ->whereRaw($this->predictedDateExpr() . ' >= ?', [now()])
            ->whereBetween(DB::raw($this->predictedDateExpr()), [$start, $end]);
        $this->applyCompanyFilter($previsaoDaily);
        $this->applyMonetaryJoin($previsaoDaily);

        $previsaoRows = $previsaoDaily
            ->selectRaw('
                DATE(' . $this->predictedDateExpr() . ') as dia_ref,
                SUM(' . $this->monetaryAmountSql() . ') as valor_prev,
                COUNT(*)   as qtd_prev
            ')
            ->groupBy('dia_ref')
            ->get()
            ->keyBy('dia_ref');

        $labels         = [];
        $dataQtdReal    = [];
        $dataQtdNao     = [];
        $dataQtdPrev    = [];
        $dataValorReal  = [];
        $dataValorNao   = [];
        $dataValorPrev  = [];

        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $labels[]        = $cursor->format('d/m/Y');
            $dataQtdReal[]   = $realizadoRows[$key]->qtd_real    ?? 0;
            $dataQtdNao[]    = $naoRows[$key]->qtd_nao           ?? 0;
            $dataQtdPrev[]   = $previsaoRows[$key]->qtd_prev     ?? 0;
            $dataValorReal[] = $realizadoRows[$key]->valor_real  ?? 0;
            $dataValorNao[]  = $naoRows[$key]->valor_nao         ?? 0;
            $dataValorPrev[] = $previsaoRows[$key]->valor_prev   ?? 0;
            $cursor->addDay();
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => 'Qtd Realizado',
                        'data'            => $dataQtdReal,
                        'backgroundColor' => 'rgba(40,167,69,0.3)',
                        'borderColor'     => '#28a745',
                        'borderWidth'     => 1,
                        'yAxisID'         => 'yLeft',
                    ],
                    [
                        'type'            => 'bar',
                        'label'           => 'Qtd Não Realizado',
                        'data'            => $dataQtdNao,
                        'backgroundColor' => 'rgba(255,193,7,0.3)',
                        'borderColor'     => '#ffc107',
                        'borderWidth'     => 1,
                        'yAxisID'         => 'yLeft',
                    ],
                    [
                        'type'            => 'bar',
                        'label'           => 'Qtd Conclusão Prevista',
                        'data'            => $dataQtdPrev,
                        'backgroundColor' => 'rgba(13,110,253,0.25)',
                        'borderColor'     => '#0d6efd',
                        'borderWidth'     => 1,
                        'yAxisID'         => 'yLeft',
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Valor Realizado (R$)',
                        'data'            => $dataValorReal,
                        'borderColor'     => '#28a745',
                        'backgroundColor' => 'rgba(40,167,69,0.1)',
                        'tension'         => 0.1,
                        'fill'            => false,
                        'yAxisID'         => 'yRight',
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Valor Não Realizado (R$)',
                        'data'            => $dataValorNao,
                        'borderColor'     => '#ffc107',
                        'backgroundColor' => 'rgba(255,193,7,0.1)',
                        'tension'         => 0.1,
                        'fill'            => false,
                        'yAxisID'         => 'yRight',
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Valor Conclusão Prevista (R$)',
                        'data'            => $dataValorPrev,
                        'borderColor'     => '#0d6efd',
                        'backgroundColor' => 'rgba(13,110,253,0.1)',
                        'tension'         => 0.1,
                        'fill'            => false,
                        'yAxisID'         => 'yRight',
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Conclusões Diárias (Realizado x Não Realizado x Prevista)',
                    ],
                ],
                'scales' => [
                    'yLeft' => [
                        'type'         => 'linear',
                        'display'      => true,
                        'position'     => 'left',
                        'title'        => ['display' => true, 'text' => 'Qtd'],
                        'beginAtZero'  => true,
                    ],
                    'yRight' => [
                        'type'         => 'linear',
                        'display'      => true,
                        'position'     => 'right',
                        'title'        => ['display' => true, 'text' => 'Valor (R$)'],
                        'beginAtZero'  => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Gráfico últimos 12 meses (close_date mensal)
     */
    public function getChartMonthlyProperty(): array
    {
        $endRef   = Carbon::now()->endOfMonth();
        $startRef = $endRef->copy()->subMonths(11)->startOfMonth();

        $monthsList = collect();
        $cursor = $startRef->copy();
        while ($cursor->lessThanOrEqualTo($endRef)) {
            $monthsList->push($cursor->copy());
            $cursor->addMonth();
        }

        $realRolling = (clone $this->realizedClosedQuery())
            ->whereBetween(
                DB::raw($this->closeDateExpr()),
                [$startRef, $endRef]
            )
            ->selectRaw('
                DATE_FORMAT(' . $this->closeDateExpr() . ', "%Y-%m") as ym_ref,
                COUNT(*) as qtd_real,
                SUM(' . $this->monetaryAmountSql() . ') as val_real
            ')
            ->groupBy('ym_ref')
            ->get()
            ->keyBy('ym_ref');

        $naoRolling = (clone $this->notRealizedClosedQuery())
            ->whereBetween(
                DB::raw($this->closeDateExpr()),
                [$startRef, $endRef]
            )
            ->selectRaw('
                DATE_FORMAT(' . $this->closeDateExpr() . ', "%Y-%m") as ym_ref,
                COUNT(*) as qtd_nao,
                SUM(' . $this->monetaryAmountSql() . ') as val_nao
            ')
            ->groupBy('ym_ref')
            ->get()
            ->keyBy('ym_ref');

        $labels  = [];
        $qtdReal = [];
        $qtdNao  = [];
        $valReal = [];
        $valNao  = [];

        foreach ($monthsList as $m) {
            $key = $m->format('Y-m');
            $labels[]  = $m->format('M/Y');
            $qtdReal[] = $realRolling[$key]->qtd_real ?? 0;
            $qtdNao[]  = $naoRolling[$key]->qtd_nao  ?? 0;
            $valReal[] = $realRolling[$key]->val_real ?? 0.0;
            $valNao[]  = $naoRolling[$key]->val_nao  ?? 0.0;
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => 'Qtd Realizado',
                        'data'            => $qtdReal,
                        'backgroundColor' => 'rgba(40,167,69,0.3)',
                        'borderColor'     => '#28a745',
                        'borderWidth'     => 1,
                        'yAxisID'         => 'yLeft',
                    ],
                    [
                        'type'            => 'bar',
                        'label'           => 'Qtd Não Realizado',
                        'data'            => $qtdNao,
                        'backgroundColor' => 'rgba(255,193,7,0.3)',
                        'borderColor'     => '#ffc107',
                        'borderWidth'     => 1,
                        'yAxisID'         => 'yLeft',
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Valor Realizado (R$)',
                        'data'            => $valReal,
                        'borderColor'     => '#28a745',
                        'backgroundColor' => 'rgba(40,167,69,0.1)',
                        'tension'         => 0.1,
                        'fill'            => false,
                        'yAxisID'         => 'yRight',
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Valor Não Realizado (R$)',
                        'data'            => $valNao,
                        'borderColor'     => '#ffc107',
                        'backgroundColor' => 'rgba(255,193,7,0.1)',
                        'tension'         => 0.1,
                        'fill'            => false,
                        'yAxisID'         => 'yRight',
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Últimos 12 Meses (Realizado x Não Realizado)',
                    ],
                ],
                'scales' => [
                    'yLeft' => [
                        'type'         => 'linear',
                        'display'      => true,
                        'position'     => 'left',
                        'title'        => ['display' => true, 'text' => 'Qtd Viabilidades'],
                        'beginAtZero'  => true,
                    ],
                    'yRight' => [
                        'type'         => 'linear',
                        'display'      => true,
                        'position'     => 'right',
                        'title'        => ['display' => true, 'text' => 'Valor (R$)'],
                        'beginAtZero'  => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Ranking das empreiteiras dentro do período (close_date)
     */
    public function getTopCompaniesProperty()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $realQ = (clone $this->realizedClosedQuery());
        $this->applyCloseDateRange($realQ, $start, $end);
        $realQ = $realQ
            ->selectRaw('company_id, SUM(' . $this->monetaryAmountSql() . ') as total_realizado')
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $naoQ = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($naoQ, $start, $end);
        $naoQ = $naoQ
            ->selectRaw('company_id, SUM(' . $this->monetaryAmountSql() . ') as total_nao')
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $companyIds = collect(array_unique(
            array_merge(
                $realQ->keys()->all(),
                $naoQ->keys()->all()
            )
        ));

        $rows = $companyIds->map(function ($cid) use ($realQ, $naoQ) {
            $real = $realQ[$cid]->total_realizado ?? 0;
            $nao  = $naoQ[$cid]->total_nao ?? 0;
            $pen  = $nao * 0.01;

            return [
                'company_id'    => $cid,
                'realizado'     => $real,
                'nao_realizado' => $nao,
                'penalidade'    => $pen,
            ];
        });

        $companies = Company::whereIn('id', $companyIds)->get()->keyBy('id');

        $rows = $rows->map(function ($row) use ($companies) {
            $row['company_name'] = $companies[$row['company_id']]->name ?? 'N/A';
            return $row;
        });

        return $rows
            ->sortByDesc('realizado')
            ->values();
    }

    /**
     * Exportações respeitando período e empresas
     */
    public function exportExcelRealized()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $data = (clone $this->realizedClosedQuery());
        $this->applyCloseDateRange($data, $start, $end);

        return (new \App\Exports\Engineers\ResumeViabilityQueryExport($data, $this->export_by, $this->amount_basis))
            ->download(date('YmdHis') . '_EngineersRealized_' . $this->export_by . '_' . $this->amount_basis . '.xlsx');
    }

    public function exportExcelNotRealized()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $data = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($data, $start, $end);

        return (new \App\Exports\Engineers\ResumeViabilityQueryExport($data, $this->export_by, $this->amount_basis))
            ->download(date('YmdHis') . '_EngineersNotRealized_' . $this->export_by . '_' . $this->amount_basis . '.xlsx');
    }

    /**
     * Listas detalhadas paginadas
     */
    public function getRealizedsProperty()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $q = (clone $this->realizedClosedQuery());
        $this->applyCloseDateRange($q, $start, $end);
        $q->select('viabilities.*')
            ->with(['Note:id,note', 'Company:id,name'])
            ->addSelect(DB::raw($this->monetaryAmountSql() . ' as money_base'));

        return $q->paginate($this->perPage, ['*'], 'realizedPage');
    }

    public function getNotRealizedsProperty()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $q = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($q, $start, $end);
        $q->select('viabilities.*')
            ->with(['Note:id,note', 'Company:id,name'])
            ->addSelect(DB::raw($this->monetaryAmountSql() . ' as money_base'));

        return $q->paginate($this->perPage, ['*'], 'notRealizedPage');
    }

    public function render()
    {
        return view('livewire.engineers.viabreports', [
            'summary'        => $this->summary,
            'chartSLA'       => $this->chartSLA,
            'chartDaily'     => $this->chartDaily,
            'chartMonthly'   => $this->chartMonthly,
            'topCompanies'   => $this->topCompanies,
            'realizeds'      => $this->realizeds,
            'notRealizeds'   => $this->notRealizeds,
            'chartRenderKey' => $this->chartRenderKey,
        ]);
    }
}
