<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Enum\ClosureOperationalStatus as Status;
use App\Http\Livewire\Closure\Dispatch\Concerns\ManagesPriorityAndTransfer;
use App\Models\{Company, User};
use App\Services\Closure\Analytics\ClosureDispatcherAnalytics;
use Livewire\Component;

class Dashboard extends Component
{
    use ManagesPriorityAndTransfer;

    public const TYPE_CHART_ID = 'closure_dash_users';

    public const STATUS_CHART_ID = 'closure_dash_status';

    public string $filterType = '';

    public ?string $filterCompany = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $staleDays = 7;

    /** Célula clicada na matriz: user (id|'none'|null), type, status. */
    public array $cell = ['user' => null, 'type' => null, 'status' => null];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['filterType', 'filterCompany', 'dateFrom', 'dateTo'], true)) {
            $this->clearCell();
        }
    }

    public function selectCell(?string $user, ?string $type, ?string $status): void
    {
        $this->cell = [
            'user'   => $user === '' ? null : $user,
            'type'   => $type === '' ? null : $type,
            'status' => $status === '' ? null : $status,
        ];
    }

    public function clearCell(): void
    {
        $this->cell = ['user' => null, 'type' => null, 'status' => null];
    }

    public function clearFilters(): void
    {
        $this->filterType    = '';
        $this->filterCompany = null;
        $this->dateFrom      = null;
        $this->dateTo        = null;
        $this->clearCell();
    }

    public function render()
    {
        $analytics = $this->analytics();
        $matrix    = $analytics->matrix($this->filters());
        $summary   = $analytics->summary($this->filters(), $matrix);
        $charts    = $this->charts($matrix);

        $this->dispatchChartUpdates($charts, $matrix['totals']['total']);

        return view('livewire.closure.dispatch.dashboard', [
            'matrix'        => $matrix,
            'summary'       => $summary,
            'statuses'      => ClosureDispatcherAnalytics::MATRIX_STATUSES,
            'interaction'   => $analytics->partnerInteraction(),
            'priorities'    => $analytics->pendingPriorityRequests(),
            'critical'      => $analytics->criticalWorks(max(1, $this->staleDays)),
            'orders'        => $analytics->orders($this->filters(), $this->cell),
            'charts'        => $charts,
            'companies'     => Company::query()->orderBy('name')->get(['id', 'name']),
            'transferUsers' => $this->transferNoteId
                ? User::query()->whereNotNull('company_id')
                    ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true))
                    ->orderBy('name')->get(['id', 'name'])
                : collect(),
            'transferStates' => $this->transferNoteId ? $analytics->transferableStates($this->transferNoteId) : collect(),
            'userChartId'    => self::TYPE_CHART_ID,
            'statusChartId'  => self::STATUS_CHART_ID,
        ]);
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'type'       => $this->filterType ?: null,
            'company_id' => $this->filterCompany ?: null,
            'from'       => $this->dateFrom ?: null,
            'to'         => $this->dateTo ?: null,
        ];
    }

    /**
     * Gráficos Chart.js. Quando não há dados o payload mantém datasets vazios na
     * renderização inicial e a view mostra um overlay; as atualizações via evento só
     * ocorrem quando há dados (o último estado do gráfico nunca é limpo).
     *
     * @param array{rows: array<int, array<string, mixed>>, totals: array<string, mixed>} $matrix
     * @return array{users: array<string, mixed>, status: array<string, mixed>}
     */
    private function charts(array $matrix): array
    {
        $rows   = collect($matrix['rows']);
        $colors = ['P' => '#E32C2C', 'M' => '#0F8A77', 'F' => '#263CC8', 'N' => '#94A3B8'];

        $datasets = [];

        foreach (['P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura', 'N' => 'Sem tipo'] as $type => $label) {
            $datasets[] = [
                'label'           => $label,
                'data'            => $rows->map(fn ($r) => $r['types'][$type])->all(),
                'backgroundColor' => $colors[$type],
                'borderColor'     => $colors[$type],
                'borderRadius'    => 4,
                'stack'           => 'tipo',
            ];
        }

        $users = [
            'type'    => 'bar',
            'data'    => ['labels' => $rows->pluck('name')->all(), 'datasets' => $datasets],
            'options' => [
                'indexAxis'           => 'y',
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend'     => ['display' => true, 'position' => 'top', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]],
                    'datalabels' => ['display' => '__VALUE_LABEL_NONZERO__', 'color' => '#fff', 'font' => ['weight' => '700', 'size' => 10]],
                ],
                'scales' => [
                    'x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => 'rgba(148, 163, 184, .18)']],
                    'y' => ['stacked' => true, 'grid' => ['display' => false]],
                ],
            ],
        ];

        $statusValues = array_map(fn ($s) => $matrix['totals']['status'][$s], ClosureDispatcherAnalytics::MATRIX_STATUSES);
        $palette      = ['#263CC8', '#A8B1E9', '#212E3E', '#F7D200', '#E32C2C', '#0F8A77', '#225E66', '#0CD3F8', '#91AFB3'];

        $status = [
            'type' => 'doughnut',
            'data' => [
                'labels'   => array_map(fn ($s) => Status::labelFor($s), ClosureDispatcherAnalytics::MATRIX_STATUSES),
                'datasets' => [['label' => 'Ordens', 'data' => $statusValues, 'backgroundColor' => $palette, 'borderColor' => '#fff', 'borderWidth' => 2]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'cutout'              => '58%',
                'plugins'             => [
                    'legend'     => ['display' => true, 'position' => 'right', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]],
                    'datalabels' => ['display' => '__VALUE_LABEL_NONZERO__', 'color' => '#fff', 'font' => ['weight' => '700', 'size' => 10]],
                ],
            ],
        ];

        return ['users' => $users, 'status' => $status];
    }

    /** @param array{users: array<string, mixed>, status: array<string, mixed>} $charts */
    private function dispatchChartUpdates(array $charts, int $total): void
    {
        if ($total === 0) {
            return; // estado vazio: overlay na view; mantém o último estado do gráfico.
        }

        $this->dispatchBrowserEvent('grafico-atualizar-' . self::TYPE_CHART_ID, $charts['users']);
        $this->dispatchBrowserEvent('grafico-atualizar-' . self::STATUS_CHART_ID, $charts['status']);
    }

}
