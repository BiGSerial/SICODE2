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

    public $perPage = 15;

    // mantido por compatibilidade, mas não vamos mais depender dele pro gráfico
    public $chartRenderKey = 0;

    protected $queryString = [
        'company_ids' => ['except' => []],
        'dt_in'       => ['except' => ''],
        'dt_out'      => ['except' => ''],
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

    /**
     * Botão "Limpar filtros"
     *
     * - volta para mês atual
     * - limpa seleção de empreiteiras (todas)
     */
    public function resetFilters()
    {
        $this->company_ids = [];

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
            $query->whereIn('company_id', $this->company_ids);
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

    /**
     * Base: concluídas
     */
    protected function baseClosedQuery()
    {
        $q = Viability::query()
            ->where('completed', true);

        $this->applyCompanyFilter($q);

        return $q;
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
        $realizedValue = $realizedValueQ->sum('value');

        $notRealizedValueQ = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($notRealizedValueQ, $start, $end);
        $notRealizedValue = $notRealizedValueQ->sum('value');

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

        $realizadoDaily = (clone $this->realizedClosedQuery());
        $this->applyCloseDateRange($realizadoDaily, $start, $end);

        $realizadoRows = $realizadoDaily
            ->selectRaw('
                DATE(' . $this->closeDateExpr() . ') as dia_ref,
                SUM(value) as valor_real,
                COUNT(*)   as qtd_real
            ')
            ->groupBy('dia_ref')
            ->get()
            ->keyBy('dia_ref');

        $naoDaily = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($naoDaily, $start, $end);

        $naoRows = $naoDaily
            ->selectRaw('
                DATE(' . $this->closeDateExpr() . ') as dia_ref,
                SUM(value) as valor_nao,
                COUNT(*)   as qtd_nao
            ')
            ->groupBy('dia_ref')
            ->get()
            ->keyBy('dia_ref');

        $labels         = [];
        $dataQtdReal    = [];
        $dataQtdNao     = [];
        $dataValorReal  = [];
        $dataValorNao   = [];

        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $labels[]        = $cursor->format('d/m/Y');
            $dataQtdReal[]   = $realizadoRows[$key]->qtd_real    ?? 0;
            $dataQtdNao[]    = $naoRows[$key]->qtd_nao           ?? 0;
            $dataValorReal[] = $realizadoRows[$key]->valor_real  ?? 0;
            $dataValorNao[]  = $naoRows[$key]->valor_nao         ?? 0;
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
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Conclusões Diárias (Realizado x Não Realizado)',
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
                SUM(value) as val_real
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
                SUM(value) as val_nao
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
            ->selectRaw('company_id, SUM(value) as total_realizado')
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $naoQ = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($naoQ, $start, $end);
        $naoQ = $naoQ
            ->selectRaw('company_id, SUM(value) as total_nao')
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

        return (new \App\Exports\Engineers\ResumeViabilityQueryExport($data))
            ->download(date('YmdHis') . '_EngineersRealized.xlsx');
    }

    public function exportExcelNotRealized()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $data = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($data, $start, $end);

        return (new \App\Exports\Engineers\ResumeViabilityQueryExport($data))
            ->download(date('YmdHis') . '_EngineersNotRealized.xlsx');
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

        return $q->paginate($this->perPage, ['*'], 'realizedPage');
    }

    public function getNotRealizedsProperty()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $q = (clone $this->notRealizedClosedQuery());
        $this->applyCloseDateRange($q, $start, $end);

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
