<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Http\Livewire\Closure\Dispatch\Concerns\ManagesPriorityAndTransfer;
use App\Models\{Company, User};
use App\Services\Closure\Analytics\{ClosureDispatcherAnalytics, ClosureManagementAnalytics, ClosureManagementCharts};
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Painel analítico do Encerramento (padrão docs/PADRAO_DASHBOARDS.md; regras em docs/encerramento/PAINEL_GERENCIAL.md).
 * O componente só guarda filtros e orquestra: o gerencial (problemas, parceiras, atividades, comparador mensal) vem de
 * `ClosureManagementAnalytics`, a operação (matriz, obras críticas) de `ClosureDispatcherAnalytics` e os gráficos de `ClosureManagementCharts`.
 */
class Dashboard extends Component
{
    use ManagesPriorityAndTransfer;

    public const PRESETS = [
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
        '90d'   => 'Últimos 90 dias',
        'year'  => 'Ano',
    ];

    /** Período do bloco gerencial (problemas, parceiras, atividades); o comparador mensal termina no mês de `periodTo`. */
    public string $periodFrom = '';

    public string $periodTo = '';

    /** Filtros da parte operacional (matriz e lista de Ordens). */
    public string $filterType = '';

    public ?string $filterCompany = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $staleDays = 7;

    /** Célula clicada na matriz: user (id|'none'|null), type, status. */
    public array $cell = ['user' => null, 'type' => null, 'status' => null];

    protected $queryString = [
        'periodFrom'    => ['except' => '', 'as' => 'de'],
        'periodTo'      => ['except' => '', 'as' => 'ate'],
        'filterType'    => ['except' => '', 'as' => 'tipo'],
        'filterCompany' => ['except' => null, 'as' => 'empresa'],
        'dateFrom'      => ['except' => null, 'as' => 'status_desde'],
        'dateTo'        => ['except' => null, 'as' => 'status_ate'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);

        Carbon::setLocale('pt_BR');

        if (blank($this->periodFrom) || blank($this->periodTo)) {
            $this->applyPreset('month');
        }

        $this->normalizePeriod();
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['periodFrom', 'periodTo'], true)) {
            $this->normalizePeriod();
            $this->refreshCharts();
        }

        if (in_array($property, ['filterType', 'filterCompany', 'dateFrom', 'dateTo'], true)) {
            $this->clearCell();
            $this->refreshCharts();
        }
    }

    public function setPreset(string $preset): void
    {
        if (array_key_exists($preset, self::PRESETS)) {
            $this->applyPreset($preset);
            $this->refreshCharts();
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
        $this->applyPreset('month');
        $this->clearCell();
        $this->refreshCharts();
    }

    /** Exporta o painel em Excel (uma aba por bloco + atividades por status), com os filtros atuais; avisa quando estiver pronto. */
    public function exportToExcel(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);

        \App\Jobs\Closure\ExportClosureDashboardJob::dispatch($this->filters(), max(1, $this->staleDays), (string) auth()->id());

        $this->dispatchBrowserEvent('closure:toast', ['message' => 'Exportação iniciada. Você será avisado quando o Excel do painel estiver pronto.', 'type' => 'info']);
    }

    public function render()
    {
        $analytics  = $this->analytics();
        $matrix     = $analytics->matrix($this->filters());
        $summary    = $analytics->summary($this->filters(), $matrix);
        $management = $this->management();

        return view('livewire.closure.dispatch.dashboard', [
            'matrix'        => $matrix,
            'summary'       => $summary,
            'statuses'      => ClosureDispatcherAnalytics::MATRIX_STATUSES,
            'interaction'   => $analytics->partnerInteraction(),
            'priorities'    => $analytics->pendingPriorityRequests(),
            'critical'      => $analytics->criticalWorks(max(1, $this->staleDays)),
            'orders'        => $analytics->orders($this->filters(), $this->cell),
            'management'    => $management,
            'charts'        => ClosureManagementCharts::all($management, $matrix),
            'companies'     => Company::query()->orderBy('name')->get(['id', 'name']),
            'transferUsers' => $this->transferNoteId
                ? User::query()->whereNotNull('company_id')
                    ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true))
                    ->orderBy('name')->get(['id', 'name'])
                : collect(),
            'transferStates' => $this->transferNoteId ? $analytics->transferableStates($this->transferNoteId) : collect(),
        ]);
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'type'        => $this->filterType ?: null,
            'company_id'  => $this->filterCompany ?: null,
            'from'        => $this->dateFrom ?: null,
            'to'          => $this->dateTo ?: null,
            'period_from' => $this->periodFrom,
            'period_to'   => $this->periodTo,
        ];
    }

    /** @return array<string, mixed> */
    private function management(): array
    {
        return app(ClosureManagementAnalytics::class)->snapshot(['from' => $this->periodFrom, 'to' => $this->periodTo]);
    }

    /** Valores inválidos da URL viram o padrão; datas trocadas são corrigidas. */
    private function normalizePeriod(): void
    {
        $f = app(ClosureManagementAnalytics::class)->normalize(['from' => $this->periodFrom, 'to' => $this->periodTo]);

        $this->periodFrom = $f['from']->toDateString();
        $this->periodTo   = $f['to']->toDateString();
    }

    private function applyPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'prev'  => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '90d'   => [now()->subDays(89), now()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $this->periodFrom = $from->toDateString();
        $this->periodTo   = $to->toDateString();
    }

    /** Envia os gráficos; o JS (`my-panel.js`) os aplica in-place, sem recriar nem limpar. */
    private function refreshCharts(): void
    {
        $analytics = $this->analytics();
        $matrix    = $analytics->matrix($this->filters());

        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => ClosureManagementCharts::all($this->management(), $matrix)]);
    }
}
