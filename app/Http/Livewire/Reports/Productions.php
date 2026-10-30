<?php

namespace App\Http\Livewire\Reports;

use App\Helpers\TextFormatter;
use App\Jobs\Reports\ProductionReportExportJob;
use App\Services\Reports\Production\{ProductionReportAnalytics, ProductionReportCharts};
use Carbon\Carbon;
use Livewire\{Component, WithPagination};

/**
 * Relatório de Produção (Serviços → Relatórios): lista do mais novo para o mais antigo, com busca completa (Nota, Ordem, Material, Grupos),
 * filtros por atividade, empresa, pessoa, conclusão, órgão externo e meio ambiente, e os indicadores da mesma consulta.
 * A tela só orquestra filtros; os números vêm de ProductionReportAnalytics e os gráficos de ProductionReportCharts (`my-panel.js`).
 */
class Productions extends Component
{
    use TextFormatter;
    use WithPagination;

    public const PRESETS = [
        '7d'    => 'Últimos 7 dias',
        '30d'   => 'Últimos 30 dias',
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
        'all'   => 'Todo o período',
    ];

    public const PER_PAGE = 50;

    public const BREAKDOWN_VISIBLE = 10;

    protected $paginationTheme = 'bootstrap';

    public string $preset = '30d';

    public ?string $monthYear = null;

    public ?string $dt_init = null;

    public ?string $dt_end = null;

    /** @var array<int, string> uuids das atividades */
    public array $service = [];

    public ?string $company = null;

    public ?string $selectedUser = null;

    /** @var array<int, string> */
    public array $conclusions = [];

    public string $doe = '';

    public string $ma = '';

    public string $confirmed = '';

    public bool $complete = false;

    public bool $d5 = false;

    public string $search = '';

    /** @var array<int, string> */
    public array $multisearch = [];

    public ?string $advanceSearch = null;

    /** Partes dos indicadores já entregues (`ProductionReportAnalytics::PARTS`); a lista abre na hora. */
    public array $loaded = ['summary' => false, 'breakdown' => false, 'conclusions' => false];

    protected $queryString = [
        'preset'       => ['except' => '30d', 'as' => 'periodo'],
        'dt_init'      => ['except' => '', 'as' => 'de'],
        'dt_end'       => ['except' => '', 'as' => 'ate'],
        'service'      => ['except' => [], 'as' => 'servicos'],
        'company'      => ['except' => '', 'as' => 'empresa'],
        'selectedUser' => ['except' => '', 'as' => 'usuario'],
        'conclusions'  => ['except' => [], 'as' => 'conclusoes'],
        'doe'          => ['except' => '', 'as' => 'orgao'],
        'ma'           => ['except' => '', 'as' => 'ma'],
        'confirmed'    => ['except' => '', 'as' => 'contabilizado'],
        'complete'     => ['except' => false, 'as' => 'abertas'],
        'd5'           => ['except' => false, 'as' => 'ri'],
        'search'       => ['except' => '', 'as' => 'busca'],
    ];

    public function mount(): void
    {
        Carbon::setLocale('pt_BR');

        if ($this->preset !== 'custom') {
            $this->applyPreset($this->preset);
        }
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
    }

    /** Libera a próxima parte ainda não carregada (encadeado pelo wire:init da view). */
    public function loadNext(): void
    {
        foreach (ProductionReportAnalytics::PARTS as $part) {
            if (!($this->loaded[$part] ?? false)) {
                $this->loaded[$part] = true;

                return;
            }
        }
    }

    public function setPreset(string $preset): void
    {
        $this->monthYear = null;
        $this->applyPreset($preset);
        $this->afterFilter();
    }

    public function updated(string $name): void
    {
        if ($name === 'monthYear') {
            $this->applyMonth();
        } elseif (in_array($name, ['dt_init', 'dt_end'], true)) {
            $this->preset    = 'custom';
            $this->monthYear = null;
        } elseif ($name === 'company') {
            $this->company = $this->company ?: null;

            if ($this->selectedUser && !$this->analytics()->people($this->company, $this->scope())->contains('id', $this->selectedUser)) {
                $this->selectedUser = null;
            }
        } elseif ($name === 'selectedUser') {
            $this->selectedUser = $this->selectedUser ?: null;
        } elseif ($name === 'search') {
            $this->search      = trim($this->search);
            $this->multisearch = [];
        }

        $this->afterFilter();
    }

    public function buscarMulti(): void
    {
        if (trim((string) $this->advanceSearch) !== '') {
            $this->multisearch   = $this->formatTextToArray($this->advanceSearch);
            $this->search        = '';
            $this->advanceSearch = '';
            $this->afterFilter();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function clearMulti(): void
    {
        $this->multisearch = [];
        $this->afterFilter();
    }

    /** Clique numa conclusão do painel: filtra a lista nela. */
    public function focusConclusion(string $conclusion): void
    {
        $this->conclusions = [$conclusion];
        $this->afterFilter();
    }

    public function cleanAll(): void
    {
        $this->service  = $this->conclusions = $this->multisearch = [];
        $this->company  = $this->selectedUser = $this->monthYear = $this->advanceSearch = null;
        $this->doe      = $this->ma = $this->confirmed = $this->search = '';
        $this->complete = $this->d5 = false;
        $this->applyPreset('30d');
        $this->afterFilter();
    }

    public function Export(): void
    {
        ProductionReportExportJob::dispatch($this->exportParams(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O relatório está sendo gerado em Excel, com os filtros atuais. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    /** @return array<string, mixed> indicadores da mesma consulta da lista, calculados uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        $analytics = $this->analytics();
        $filters   = $analytics->normalize($this->filters());
        $parts     = $analytics->screenAllowed($filters)
            ? array_values(array_filter(ProductionReportAnalytics::PARTS, fn (string $part) => $this->loaded[$part] ?? false))
            : [];

        return $analytics->assemble($filters, $parts, cache: true);
    }

    public function render()
    {
        $analytics = $this->analytics();
        $filters   = $analytics->normalize($this->filters());
        $allowed   = $analytics->screenAllowed($filters);
        // Em período longo o total exigiria varrer o período todo: a lista pagina sem contagem e o Excel traz tudo.
        $lists    = $allowed ? $analytics->listQuery($filters)->paginate(self::PER_PAGE) : $analytics->listQuery($filters)->simplePaginate(self::PER_PAGE);
        $snapshot = $this->snapshot;

        return view('livewire.reports.productions', [
            'lists'          => $lists,
            'snapshot'       => $snapshot,
            'charts'         => ProductionReportCharts::all($snapshot),
            'allowed'        => $allowed,
            'maxDays'        => ProductionReportAnalytics::SCREEN_MAX_DAYS,
            'nextPart'       => $allowed ? collect(ProductionReportAnalytics::PARTS)->first(fn (string $part) => !($this->loaded[$part] ?? false)) : null,
            'serviceList'    => $analytics->services(),
            'companyList'    => $this->scope() === null ? $analytics->companies() : $analytics->companies($this->scope()),
            'peopleList'     => $analytics->people($this->company, $this->scope()),
            'conclusionList' => $analytics->conclusions(),
            'period'         => $filters['from'] || $filters['to']
                ? ($filters['from']?->format('d/m/Y') ?? 'início') . ' a ' . ($filters['to']?->format('d/m/Y') ?? 'hoje')
                : 'todo o histórico',
            'canPickCompany' => !auth()->user()->contract,
            'hasFilters'     => (bool) ($this->service || $this->company || $this->selectedUser || $this->conclusions || $this->doe || $this->ma || $this->confirmed || $this->complete || $this->d5 || $this->search || $this->multisearch),
        ]);
    }

    private function analytics(): ProductionReportAnalytics
    {
        return app(ProductionReportAnalytics::class);
    }

    /** @return array<int, string>|null empresas visíveis ao usuário (null = todas) */
    private function scope(): ?array
    {
        return $this->analytics()->scopeFor(auth()->user());
    }

    /** @return array<string, mixed> */
    private function filters(): array
    {
        return [
            'from'         => $this->dt_init,
            'to'           => $this->dt_end,
            'services'     => $this->service,
            'company'      => $this->company,
            'user'         => $this->selectedUser,
            'conclusions'  => $this->conclusions,
            'doe'          => $this->doe,
            'ma'           => $this->ma,
            'confirmed'    => $this->confirmed,
            'include_open' => $this->complete,
            'include_ri'   => $this->d5,
            'search'       => $this->search,
            'multisearch'  => $this->multisearch,
            'scope'        => $this->scope(),
        ];
    }

    /** @return array<string, mixed> o mesmo filtro, só com tipos simples (vai para a fila) */
    private function exportParams(): array
    {
        return array_diff_key($this->filters(), ['scope' => 1]);
    }

    private function afterFilter(): void
    {
        $this->resetPage();
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => ProductionReportCharts::all($this->snapshot)]);
    }

    private function applyMonth(): void
    {
        if (!$this->monthYear) {
            return;
        }

        $month         = Carbon::parse($this->monthYear . '-01');
        $this->preset  = 'custom';
        $this->dt_init = $month->copy()->startOfMonth()->toDateString();
        $this->dt_end  = $month->copy()->endOfMonth()->toDateString();
    }

    private function applyPreset(string $preset): void
    {
        $today = now();

        [$from, $to] = match ($preset) {
            '7d'    => [$today->copy()->subDays(6), $today],
            'month' => [$today->copy()->startOfMonth(), $today],
            'prev'  => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'all'   => [null, null],
            default => [$today->copy()->subDays(29), $today],
        };

        $this->preset  = array_key_exists($preset, self::PRESETS) ? $preset : '30d';
        $this->dt_init = $from?->toDateString();
        $this->dt_end  = $to?->toDateString();
    }
}
