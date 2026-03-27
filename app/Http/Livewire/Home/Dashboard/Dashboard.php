<?php

namespace App\Http\Livewire\Home\Dashboard;

use App\Jobs\Home\PersonalProductionsJob;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $production;
    public $viabilities;
    public $selectedMonth;
    public $recentActivity;
    public $recentFilter = 'today';
    public $recentFilterName = 'Hoje';
    public $services;
    public $selectedService;
    public $dt_in;
    public $dt_out;
    public $includeOpen = false;
    public $includeRi = false;


    // PROPRIEDADES PARA ARMAZENAR OS DADOS CALCULADOS
    public Collection $currentMonthData;
    public Collection $previousMonthData;
    public Collection $currentPeriodData;
    public Collection $previousPeriodData;


    protected $queryString = [
        'selectedMonth' => ['except' => '', 'as' => 'mes'],
        'selectedService' => ['except' => '', 'as' => 'servico'],
        'dt_in' => ['except' => '', 'as' => 'dtin'],
        'dt_out' => ['except' => '', 'as' => 'dtout'],
        'includeOpen' => ['except' => false, 'as' => 'aberto'],
        'includeRi' => ['except' => false, 'as' => 'ri'],
    ];

    public function mount()
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->dt_in = now()->startOfMonth()->format('Y-m-d');
        $this->dt_out = now()->format('Y-m-d');

        $this->services = Service::whereIn('uuid', Production::where('user_id', auth()->id())
            ->where('rejected', false)
            ->where('d5', false)
            ->distinct('service_id')
            ->pluck('service_id'))
            ->orderBy('service')
            ->get();

        // Carrega os dados iniciais
        $this->loadDataForMonth();
        $this->loadDataForCustomPeriod();
        $this->updatedRecentFilter($this->recentFilter);
    }


    public function exportToExcel()
    {
        $params = [
            'service'     => $this->selectedService ? [$this->selectedService] : [], // UUID(s)
            'dt_init'     => $this->dt_in,   // 'Y-m-d'
            'dt_end'      => $this->dt_out,  // 'Y-m-d'
            'monthYear'   => $this->selectedMonth, // referência visual do período no dashboard
            'include_open' => (bool) $this->includeOpen,
            'include_ri'   => (bool) $this->includeRi,
        ];

        PersonalProductionsJob::dispatch($params, auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>Seu relatório pessoal está sendo gerado. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function updatedRecentFilter($value)
    {
        $this->recentFilterName = match ($value) {
            'today' => 'Hoje',
            'month' => 'Mês',
            'year' => 'Ano',
            default => 'Hoje',
        };

        $this->recentActivity = Notetimeline::where('user_id', auth()->id())
            ->when($value === 'today', function ($query) {
                return $query->whereDate('created_at', today());
            })
            ->when($value === 'month', function ($query) {
                return $query->whereMonth('created_at', now()->month);
            })
            ->when($value === 'year', function ($query) {
                return $query->whereYear('created_at', now()->year);
            })
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->with(['Note:id,note', 'Service:id,service'])
            ->get();
    }



    protected function baseQuery()
    {
        return Production::query()
            ->where('user_id', auth()->id())
            ->when($this->selectedService, function ($query) {
                return $query->where('service_id', $this->selectedService);
            })
            ->where('completed', true)
            ->where('rejected', false)
            ->where('d5', false);
    }


    /**
     * Método central para carregar todos os dados necessários de uma vez.
     */
    protected function loadDataForMonth()
    {
        // 1. Pegue o ano e o mês da sua string de entrada.
        list($year, $month) = explode('-', $this->selectedMonth);

        // 2. Crie o objeto Carbon para o mês selecionado usando Carbon::create().
        // Este método é explícito e garante que o dia será 1 e a hora será meia-noite.
        $selected = Carbon::create((int)$year, (int)$month, 1)->startOfDay();

        // 3. Calcule o ano e o mês anteriores de forma numérica.
        $previousYear = (int)$year;
        $previousMonth = (int)$month - 1;

        // Trata o caso de Janeiro -> Dezembro do ano anterior.
        if ($previousMonth === 0) {
            $previousMonth = 12;
            $previousYear--;
        }

        // 4. Crie o objeto Carbon para o mês anterior da mesma forma segura.
        $previous = Carbon::create($previousYear, $previousMonth, 1)->startOfDay();


        // Agora, $selected será '2025-05-01' e $previous será '2025-04-01', como esperado.
        // O resto do seu código funcionará corretamente.
        $this->currentMonthData = $this->getDataByMonth($selected)
            ->selectRaw('DATE(completed_at) as date, SUM(postes_u) as postes, COUNT(*) as notas')
            ->groupBy('date')
            ->get();

        $this->previousMonthData = $this->getDataByMonth($previous)
            ->selectRaw('DATE(completed_at) as date, SUM(postes_u) as postes, COUNT(*) as notas')
            ->groupBy('date')
            ->get();
    }

    // Novo método para carregar dados do período personalizado
    protected function loadDataForCustomPeriod()
    {
        // Certifica-se de que as datas estão formatadas corretamente
        $dt_in = Carbon::parse($this->dt_in)->startOfDay();
        $dt_out = Carbon::parse($this->dt_out)->endOfDay();

        // Calcula o período anterior.
        $interval = $dt_out->diff($dt_in);
        $previous_dt_in = $dt_in->copy()->sub($interval)->subDay(); // Subtrai o intervalo de tempo + 1 dia para pegar o período anterior
        $previous_dt_out = $dt_out->copy()->sub($interval)->subDay();

        $this->currentPeriodData = $this->baseQuery()
            ->whereBetween('completed_at', [$dt_in, $dt_out])
            ->selectRaw('DATE(completed_at) as date, SUM(postes_u) as postes, COUNT(*) as notas')
            ->groupBy('date')
            ->get();

        $this->previousPeriodData = $this->baseQuery()
            ->whereBetween('completed_at', [$previous_dt_in, $previous_dt_out])
            ->selectRaw('DATE(completed_at) as date, SUM(postes_u) as postes, COUNT(*) as notas')
            ->groupBy('date')
            ->get();
    }


    public function updatedSelectedMonth()
    {
        $this->dt_in = Carbon::parse($this->selectedMonth)->startOfMonth()->format('Y-m-d');
        $this->dt_out = Carbon::parse($this->selectedMonth)->endOfMonth()->format('Y-m-d');

        if ($this->dt_out > now()->format('Y-m-d')) {
            $this->dt_out = now()->format('Y-m-d');
        }

        $this->loadDataForMonth();
        $this->loadDataForCustomPeriod();
        $this->dispatchBrowserEvent('grafico-atualizar-mesAtual', $this->daily);
        $this->dispatchBrowserEvent('grafico-atualizar-acumuladoMensal', $this->monthly);
    }

    public function updatedSelectedService()
    {
        $this->dt_in = Carbon::parse($this->selectedMonth)->startOfMonth()->format('Y-m-d');
        $this->dt_out = Carbon::parse($this->selectedMonth)->endOfMonth()->format('Y-m-d');

        if ($this->dt_out > now()->format('Y-m-d')) {
            $this->dt_out = now()->format('Y-m-d');
        }

        $this->loadDataForMonth();
        $this->loadDataForCustomPeriod();
        $this->dispatchBrowserEvent('grafico-atualizar-mesAtual', $this->daily);
        $this->dispatchBrowserEvent('grafico-atualizar-acumuladoMensal', $this->monthly);
    }

    public function updatedDtIn()
    {
        $this->loadDataForCustomPeriod();
        $this->dispatchBrowserEvent('grafico-atualizar-mesAtual', $this->daily);
    }

    public function updatedDtOut()
    {
        $this->loadDataForCustomPeriod();
        $this->dispatchBrowserEvent('grafico-atualizar-mesAtual', $this->daily);
    }

    protected function getDataByMonth(Carbon $month)
    {
        return $this->baseQuery()
            ->whereBetween('completed_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()
            ]);
    }

    // A propriedade computada para os cartões de resumo
    public function getDataProperty(): array
    {
        $totalNotasMes = $this->currentPeriodData->sum('notas');
        $totalPostesMes = $this->currentPeriodData->sum('postes');
        $totalNotasMesAnterior = $this->previousPeriodData->sum('notas');
        $totalPostesMesAnterior = $this->previousPeriodData->sum('postes');

        // Note: A lógica para "hoje" e "ontem" precisa ser ajustada para o período customizado.
        // No entanto, para manter a lógica original, vamos usá-la.
        $hoje = now()->format('Y-m-d');
        $ontem = now()->subDay()->format('Y-m-d');
        $registroHoje = $this->currentPeriodData->first(fn ($d) => Carbon::parse($d->date)->format('Y-m-d') === $hoje);
        $registroOntem = $this->currentPeriodData->first(fn ($d) => Carbon::parse($d->date)->format('Y-m-d') === $ontem);

        $growthPostes = $totalPostesMesAnterior == 0
            ? ($totalPostesMes > 0 ? 100 : 0)
            : (($totalPostesMes - $totalPostesMesAnterior) / $totalPostesMesAnterior) * 100;

        $growthNotas = $totalNotasMesAnterior == 0
            ? ($totalNotasMes > 0 ? 100 : 0)
            : (($totalNotasMes - $totalNotasMesAnterior) / $totalNotasMesAnterior) * 100;

        $totalPostesHoje = $registroHoje->postes ?? 0;
        $totalPostesOntem = $registroOntem->postes ?? 0;

        $growthPostesHoje = $totalPostesOntem == 0
            ? ($totalPostesHoje > 0 ? 100 : 0)
            : (($totalPostesHoje - $totalPostesOntem) / $totalPostesOntem) * 100;

        $totalNotasHoje = $registroHoje->notas ?? 0;
        $totalNotasOntem = $registroOntem->notas ?? 0;

        $growthNotasHoje = $totalNotasOntem == 0
            ? ($totalNotasHoje > 0 ? 100 : 0)
            : (($totalNotasHoje - $totalNotasOntem) / $totalNotasOntem) * 100;

        return [
            'selectedMonth' => $this->selectedMonth,
            'mes' => Carbon::parse($this->dt_in)->translatedFormat('d/M/Y') . ' - ' . Carbon::parse($this->dt_out)->translatedFormat('d/M/Y'),
            'ano' => Carbon::parse($this->dt_in)->year,
            'totalNotasMes' => $totalNotasMes,
            'totalPostesMes' => $totalPostesMes,
            'totalNotasHoje' => $totalNotasHoje,
            'totalPostesHoje' => $totalPostesHoje,
            'totalNotasOntem' => $totalNotasOntem,
            'totalPostesOntem' => $totalPostesOntem,
            'mesAnterior' => Carbon::parse($this->dt_in)->subMonth()->translatedFormat('M/Y'),
            'anoAnterior' => Carbon::parse($this->dt_in)->subMonth()->year,
            'totalNotasMesAnterior' => $totalNotasMesAnterior,
            'totalPostesMesAnterior' => $totalPostesMesAnterior,
            'totalNotasHojeAnterior' => 0, // Como é um período dinâmico, este valor pode não ser relevante.
            'totalPostesHojeAnterior' => 0, // Como é um período dinâmico, este valor pode não ser relevante.
            'growthPostes' => $growthPostes,
            'growthNotas' => $growthNotas,
            'growthPostesHoje' => $growthPostesHoje,
            'growthNotasHoje' => $growthNotasHoje,
        ];
    }



    protected function getGlobalAverage($dateStart, $dateEnd, $period = 'day')
    {
        $query = Production::query()
            ->when($this->selectedService, function ($query) {
                return $query->where('service_id', $this->selectedService);
            })
            ->where('completed', true)
            ->where('rejected', false)
            ->where('d5', false)
            ->whereBetween('completed_at', [$dateStart, $dateEnd]);

        if ($period === 'day') {
            $results = $query->selectRaw('DATE(completed_at) as date, SUM(postes_u) as postes')
                ->groupBy('date')
                ->get();
            $totais = $results->pluck('postes')->toArray();
        } else { // 'month'
            $results = $query->selectRaw('YEAR(completed_at) as year, MONTH(completed_at) as month, SUM(postes_u) as postes')
                ->groupBy('year', 'month')
                ->get();
            $totais = $results->pluck('postes')->toArray();
        }

        return count($totais) ? array_sum($totais) / count($totais) : 0;
    }


    protected function getGlobalAverageByLabels($labels, $granularity = 'day')
    {
        if (empty($labels)) {
            return 0;
        }

        $query = Production::query()
            ->when($this->selectedService, fn ($q) => $q->where('service_id', $this->selectedService))
            ->where('completed', true)
            ->where('rejected', false)
            ->where('d5', false);

        if ($granularity === 'day') {
            $dates = collect($labels)->map(function ($label) {
                return Carbon::createFromFormat('d/m/Y', $label)->format('Y-m-d');
            });

            // Primeiro, obtenha a produção por usuário por dia
            $dailyUserProductions = $query->whereIn(DB::raw('DATE(completed_at)'), $dates)
                ->selectRaw('DATE(completed_at) as date, user_id, SUM(postes_u) as postes_user_day, COUNT(*) as total_user_day')
                ->groupBy('date', 'user_id')
                ->get();

            $averageProductionsPerUserDay = [];

            // Calcule a média de produção (posts + total) por usuário para cada dia
            foreach ($dailyUserProductions->groupBy('date') as $date => $productions) {
                $totalProductionForDay = 0;
                $activeUsersForDay = 0;

                foreach ($productions as $production) {
                    $totalProductionForDay += ($production->postes_user_day + $production->total_user_day);
                    $activeUsersForDay++; // Conta cada user_id distinto para o dia
                }

                if ($activeUsersForDay > 0) {
                    // Média de produção por usuário para este dia
                    $averageProductionsPerUserDay[] = $totalProductionForDay / $activeUsersForDay;
                }
            }

            // Calcule a média geral das médias diárias por usuário
            if (count($averageProductionsPerUserDay) > 0) {
                return array_sum($averageProductionsPerUserDay) / count($averageProductionsPerUserDay);
            } else {
                return 0;
            }

        } else { // month
            $months = collect($labels)->map(function ($label) {
                return Carbon::createFromFormat('M/Y', $label)->format('Y-m');
            });

            // Primeiro, obtenha a produção por usuário por mês
            $monthlyUserProductions = $query->whereIn(DB::raw('DATE_FORMAT(completed_at, "%Y-%m")'), $months)
                ->selectRaw('YEAR(completed_at) as year, MONTH(completed_at) as month, user_id, SUM(postes_u) as postes_user_month, COUNT(*) as total_user_month')
                ->groupBy('year', 'month', 'user_id')
                ->get();

            $averageProductionsPerUserMonth = [];

            // Calcule a média de produção (posts + total) por usuário para cada mês
            foreach ($monthlyUserProductions->groupBy(['year', 'month']) as $year => $monthsData) {
                foreach ($monthsData as $month => $productions) {
                    $totalProductionForMonth = 0;
                    $activeUsersForMonth = 0;

                    foreach ($productions as $production) {
                        $totalProductionForMonth += ($production->postes_user_month + $production->total_user_month);
                        $activeUsersForMonth++; // Conta cada user_id distinto para o mês
                    }

                    if ($activeUsersForMonth > 0) {
                        // Média de produção por usuário para este mês
                        $averageProductionsPerUserMonth[] = $totalProductionForMonth / $activeUsersForMonth;
                    }
                }
            }

            // Calcule a média geral das médias mensais por usuário
            if (count($averageProductionsPerUserMonth) > 0) {
                return array_sum($averageProductionsPerUserMonth) / count($averageProductionsPerUserMonth);
            } else {
                return 0;
            }
        }
    }


    // A propriedade computada para o gráfico diário
    public function getDailyProperty(): array
    {
        // Labels do gráfico: todos os dias do mês atual que tiveram produção
        $chartLabels = $this->currentPeriodData->pluck('date')->map(fn ($date) => Carbon::parse($date)->format('d/m/Y'))->toArray();

        // Dados do mês atual (usuário)
        $currentMonthChartPostes = $this->currentPeriodData->pluck('postes')->toArray();
        $currentMonthChartNotas = $this->currentPeriodData->pluck('notas')->toArray();

        // Média global dos postes/ativos nos mesmos dias
        $media = $this->getGlobalAverageByLabels($chartLabels, 'day');

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $chartLabels,
                'datasets' => [
                    [
                        'type' => 'bar',
                        'label' => 'Notas',
                        'data' => $currentMonthChartNotas,
                        'backgroundColor' => 'rgba(0, 143, 251, 0.3)',
                        'borderColor' => '#008FFB',
                        'borderWidth' => 1,
                        'yAxisID' => 'yNotas',
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Postes/Ativos',
                        'data' => $currentMonthChartPostes,
                        'borderColor' => '#00E396',
                        'backgroundColor' => 'rgba(0,227,150,0.1)',
                        'tension' => 0.05,
                        'fill' => false,
                        'yAxisID' => 'yPostes',
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Média Equipe Postes/Ativos',
                        'data' => array_fill(0, count($chartLabels), $media),
                        'borderColor' => '#FBC02D',
                        'backgroundColor' => 'rgba(251,192,45,0.1)',
                        'borderDash' => [5, 5],
                        'pointRadius' => 0,
                        'tension' => 0,
                        'fill' => false,
                        'yAxisID' => 'yPostes',
                    ],
                ],
            ],
            // As opções do gráfico permanecem as mesmas
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title' => ['display' => true, 'text' => 'Notas (barras) x Postes/Ativos (linhas) por Dia'],
                ],
                'scales' => [
                    'yNotas' => ['type' => 'linear', 'display' => true, 'position' => 'left', 'title' => ['display' => true, 'text' => 'Notas'], 'beginAtZero' => true],
                    'yPostes' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'title' => ['display' => true, 'text' => 'Postes/Ativos'], 'beginAtZero' => true],
                ],
            ],
        ];
    }

    // A propriedade computada para o gráfico mensal
    public function getMonthlyProperty(): array
    {
        $selected = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        $finalMonth = $selected->copy();
        $startMonth = $selected->copy()->startOfYear();

        $months = collect();
        $current = $startMonth->copy();
        while ($current->lessThanOrEqualTo($finalMonth)) {
            $months->push($current->copy());
            $current->addMonth();
        }

        $currentYear = $selected->year;
        $prevYear = $currentYear - 1;

        $dataCurrentYear = $this->baseQuery()->whereYear('completed_at', $currentYear)->selectRaw('MONTH(completed_at) as month, SUM(postes_u) as postes, COUNT(*) as notas')->groupBy('month')->orderBy('month')->get()->keyBy('month');
        $dataPrevYear = $this->baseQuery()->whereYear('completed_at', $prevYear)->selectRaw('MONTH(completed_at) as month, SUM(postes_u) as postes, COUNT(*) as notas')->groupBy('month')->orderBy('month')->get()->keyBy('month');

        $chartLabels = $months->map(fn ($m) => $m->format('M/Y'))->toArray();

        $notasCurrent = [];
        $notasPrev = [];
        $postesCurrent = [];
        $postesPrev = [];

        foreach ($months as $month) {
            $m = $month->month;
            $notasCurrent[] = $dataCurrentYear->get($m)->notas ?? 0;
            $notasPrev[] = $dataPrevYear->get($m)->notas ?? 0;
            $postesCurrent[] = $dataCurrentYear->get($m)->postes ?? 0;
            $postesPrev[] = $dataPrevYear->get($m)->postes ?? 0;
        }

        $media = $this->getGlobalAverageByLabels($chartLabels, 'month');

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $chartLabels,
                'datasets' => [
                    ['type' => 'bar', 'label' => 'Notas - ' . $currentYear, 'data' => $notasCurrent, 'backgroundColor' => 'rgba(0,143,251,0.3)', 'borderColor' => '#008FFB', 'borderWidth' => 1, 'yAxisID' => 'yNotas'],
                    ['type' => 'bar', 'label' => 'Notas - ' . $prevYear, 'data' => $notasPrev, 'backgroundColor' => 'rgba(255,69,96,0.2)', 'borderColor' => '#FF4560', 'borderWidth' => 1, 'yAxisID' => 'yNotas'],
                    ['type' => 'line', 'label' => 'Postes/Ativos - ' . $currentYear, 'data' => $postesCurrent, 'borderColor' => '#00E396', 'backgroundColor' => 'rgba(0,227,150,0.1)', 'tension' => 0.1, 'fill' => false, 'yAxisID' => 'yPostes'],
                    ['type' => 'line', 'label' => 'Postes/Ativos - ' . $prevYear, 'data' => $postesPrev, 'borderColor' => '#775DD0', 'backgroundColor' => 'rgba(119,93,208,0.1)', 'tension' => 0.1, 'fill' => false, 'yAxisID' => 'yPostes'],
                    ['type' => 'line', 'label' => 'Média Equipe Postes/Ativos - '. $currentYear, 'data' => array_fill(0, count($chartLabels), $media), 'borderColor' => '#FBC02D', 'backgroundColor' => 'rgba(251,192,45,0.1)', 'borderDash' => [5, 5], 'pointRadius' => 0, 'tension' => 0, 'fill' => false, 'yAxisID' => 'yPostes'],
                ],
            ],
            // As opções do gráfico permanecem as mesmas
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title' => ['display' => true, 'text' => "Notas (barras) x Postes/Ativos (linhas) por Mês ({$currentYear} vs. {$prevYear})"],
                ],
                'scales' => [
                    'yNotas' => ['type' => 'linear', 'display' => true, 'position' => 'left', 'title' => ['display' => true, 'text' => 'Notas'], 'beginAtZero' => true],
                    'yPostes' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'title' => ['display' => true, 'text' => 'Postes/Ativos'], 'beginAtZero' => true],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.home.dashboard.dashboard', [
            'data' => $this->data,
            'mesAtual' => $this->daily,
            'acumuladoMensal' => $this->monthly,
        ]);
    }
}
