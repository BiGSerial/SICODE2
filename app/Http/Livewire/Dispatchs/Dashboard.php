<?php

namespace App\Http\Livewire\Dispatchs;

use App\Http\Livewire\Concerns\{WithListFilters, WithLocationFilterOptions};
use App\Jobs\Dispatchs\ActivityDashboardExportJob;
use App\Models\{Note, Service};
use App\Services\Dispatch\Dashboard\{ActivityDashboardAnalytics, ActivityDashboardCharts};
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Dashboard de atividade (docs/dashboards/DASHBOARD_ATIVIDADE.md; padrão em docs/PADRAO_DASHBOARDS.md). O componente só guarda os
 * filtros e orquestra; os números vêm de `ActivityDashboardAnalytics` e os gráficos de `ActivityDashboardCharts`.
 */
class Dashboard extends Component
{
    use WithListFilters;
    use WithLocationFilterOptions;

    public const PRESETS = [
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
        '90d'   => 'Últimos 90 dias',
        'year'  => 'Ano',
    ];

    public string $serviceId = '';

    public string $noteType = '2';

    public bool $includeTrackingRubric = false;

    public string $rangeFrom = '';

    public string $rangeTo = '';

    public string $adherenceDays = '8';

    protected $queryString = [
        'noteType'              => ['except' => '2', 'as' => 'tipo'],
        'includeTrackingRubric' => ['except' => false, 'as' => 'acomp'],
        'rangeFrom'             => ['except' => '', 'as' => 'de'],
        'rangeTo'               => ['except' => '', 'as' => 'ate'],
        'adherenceDays'         => ['except' => '8', 'as' => 'aderencia'],
        'filters'               => ['except' => [], 'as' => 'f'],
    ];

    public function mount($service): void
    {
        $this->serviceId = (string) Service::query()->where('uuid', $service)->firstOrFail()->uuid;

        Carbon::setLocale('pt_BR');

        if (blank($this->rangeFrom) || blank($this->rangeTo)) {
            $this->applyPreset('month');
        }

        $this->normalize();
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
    }

    public function setPreset(string $preset): void
    {
        if (array_key_exists($preset, self::PRESETS)) {
            $this->applyPreset($preset);
            $this->refreshCharts();
        }
    }

    public function clearFilters(): void
    {
        $this->noteType              = '2';
        $this->includeTrackingRubric = false;
        $this->adherenceDays         = '8';
        $this->filters               = [];
        $this->applyPreset('month');
        $this->refreshCharts();
    }

    public function updated($name): void
    {
        if ($name === 'filters' || str_starts_with((string) $name, 'filters.')) {
            $this->filters = $this->normalizeFilters($this->filters);
        }

        $this->normalize();
        $this->refreshCharts();
    }

    public function exportToExcel(): void
    {
        ActivityDashboardExportJob::dispatch($this->serviceId, $this->filters(), auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O dashboard está sendo gerado em Excel. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function getServiceProperty(): Service
    {
        return Service::query()->with('Status')->where('uuid', $this->serviceId)->firstOrFail();
    }

    public function getSnapshotProperty(): array
    {
        return ActivityDashboardAnalytics::for($this->service)->snapshot($this->service, $this->filters());
    }

    public function render()
    {
        $snapshot = $this->snapshot;

        return view('livewire.dispatchs.dashboard', [
            'service'  => $this->service,
            'snapshot' => $snapshot,
            'charts'   => ActivityDashboardCharts::all($snapshot),
        ]);
    }

    /** @return array{type: string, tracking: bool, from: string, to: string, limit: string} */
    private function filters(): array
    {
        return ['type' => $this->noteType, 'tracking' => $this->includeTrackingRubric, 'from' => $this->rangeFrom, 'to' => $this->rangeTo, 'limit' => $this->adherenceDays, 'scope' => $this->filters];
    }

    // ---------- Filtros de recorte (WithListFilters): os mesmos da Lista para Desenho ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'group1'   => ['label' => 'Grupo1'],
            'group2'   => ['label' => 'Grupo2'],
            'group5'   => ['label' => 'Grupo5'],
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        $distinct = fn (string $column) => $this->rememberFilterOptions($column, fn () => Note::query()
            ->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderBy($column)->pluck($column)
            ->map(fn ($value) => ['v' => (string) $value, 'l' => (string) $value])->all());

        return [
            'group1'  => $distinct('group1'),
            'group2'  => $distinct('group2'),
            'group5'  => $distinct('group5'),
            'rubrica' => $distinct('rubrica'),
        ] + $this->locationFilterOptions();
    }

    /** Mesmo cache das opções da Lista para Desenho (as listas são as mesmas). */
    protected function listFilterCacheScope(): string
    {
        return \App\Http\Livewire\Dispatchs\Desenho\Main::class;
    }

    /** O dashboard não pagina nem seleciona linhas: só recalcula os gráficos (o trait avisa o painel de filtros). */
    protected function afterListFiltersChanged(): void
    {
        $this->dispatchBrowserEvent('ops-filters', ['sel' => (object) $this->filters]);
        $this->refreshCharts();
    }

    /** Valores inválidos da URL viram o padrão; datas trocadas são corrigidas. */
    private function normalize(): void
    {
        $f = app(ActivityDashboardAnalytics::class)->normalize($this->filters());

        $this->noteType      = $f['type'];
        $this->rangeFrom     = $f['from']->toDateString();
        $this->rangeTo       = $f['to']->toDateString();
        $this->adherenceDays = (string) $f['limit'];
    }

    private function applyPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'prev'  => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '90d'   => [now()->subDays(89), now()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $this->rangeFrom = $from->toDateString();
        $this->rangeTo   = $to->toDateString();
    }

    /** Envia os gráficos novos; o JS (`my-panel.js`) os aplica in-place, sem recriar. */
    private function refreshCharts(): void
    {
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => ActivityDashboardCharts::all($this->snapshot)]);
    }
}
