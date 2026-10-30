<?php

namespace App\Http\Livewire\Engineers;

use App\Jobs\Engineers\InformDashboardExportJob;
use App\Services\Engineers\{InformDashboardAnalytics, InformDashboardCharts};
use Carbon\Carbon;
use Livewire\{Component, WithPagination};

/**
 * Painel de informes (Engenharia → Informes): recebimento, devoluções, entrega da ADS no prazo e situação atual dos informes de conclusão.
 * A tela só orquestra filtros; os números vêm de InformDashboardAnalytics e os gráficos de InformDashboardCharts (`my-panel.js`).
 */
class InformDashboard extends Component
{
    use WithPagination;

    public const PRESETS = [
        '7d'    => 'Últimos 7 dias',
        '30d'   => 'Últimos 30 dias',
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
        '90d'   => 'Últimos 90 dias',
    ];

    public const PER_PAGE = 25;

    public const BREAKDOWN_VISIBLE = 10;

    protected $paginationTheme = 'bootstrap';

    public string $preset = 'month';

    public ?string $monthYear = null;

    public ?string $dt_init = null;

    public ?string $dt_end = null;

    public ?string $company = null;

    public string $search = '';

    public string $returned = '';

    public string $retry = '';

    public ?string $status = null;

    public string $ads = '';

    protected $queryString = [
        'preset'   => ['except' => 'month', 'as' => 'periodo'],
        'dt_init'  => ['except' => '', 'as' => 'de'],
        'dt_end'   => ['except' => '', 'as' => 'ate'],
        'company'  => ['except' => '', 'as' => 'empreiteira'],
        'search'   => ['except' => '', 'as' => 'busca'],
        'returned' => ['except' => '', 'as' => 'devolucao'],
        'retry'    => ['except' => '', 'as' => 'reinforme'],
        'status'   => ['except' => '', 'as' => 'situacao'],
        'ads'      => ['except' => '', 'as' => 'ads'],
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

    public function setPreset(string $preset): void
    {
        $this->monthYear = null;
        $this->applyPreset($preset);
        $this->afterFilter();
    }

    /** Atalho da lista: filtra pela situação da ADS (vazio = todas). */
    public function setAds(string $ads): void
    {
        $this->ads = array_key_exists($ads, InformDashboardAnalytics::BUCKETS) ? $ads : '';
        $this->afterFilter();
    }

    public function updated(string $name): void
    {
        if ($name === 'monthYear') {
            $this->applyMonth();
        } elseif (in_array($name, ['dt_init', 'dt_end'], true)) {
            $this->preset    = 'custom';
            $this->monthYear = null;
        } elseif (in_array($name, ['company', 'status'], true)) {
            $this->{$name} = $this->{$name} ?: null;
        } elseif ($name === 'search') {
            $this->search = trim($this->search);
        }

        $this->afterFilter();
    }

    public function cleanAll(): void
    {
        $this->company = $this->status = $this->monthYear = null;
        $this->search  = $this->returned = $this->retry = $this->ads = '';
        $this->applyPreset('month');
        $this->afterFilter();
    }

    public function Export(): void
    {
        InformDashboardExportJob::dispatch($this->exportParams(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O painel está sendo gerado em Excel, com os filtros atuais. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    /** @return array<string, mixed> recorte completo (sem a situação da ADS), calculado uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        return $this->analytics()->assemble($this->normalized(), cache: true);
    }

    public function render()
    {
        $analytics = $this->analytics();
        $filters   = $this->normalized();
        $snapshot  = $this->snapshot;
        $rows      = $analytics->listRows($snapshot, $filters);

        return view('livewire.engineers.inform-dashboard', [
            'filters'        => $filters,
            'snapshot'       => $snapshot,
            'charts'         => InformDashboardCharts::all($snapshot),
            'lists'          => $analytics->paginate($rows, $this->page ?: 1, self::PER_PAGE, url()->current()),
            'listTotal'      => $rows->count(),
            'companyList'    => $analytics->companies($this->scope()),
            'statusList'     => $analytics->statuses(),
            'canPickCompany' => !auth()->user()->contract,
            'period'         => $filters['from']->format('d/m/Y') . ' a ' . $filters['to']->format('d/m/Y'),
            'hasFilters'     => (bool) ($this->company || $this->search || $this->returned || $this->retry || $this->status || $this->ads || $this->preset !== 'month'),
        ]);
    }

    private function analytics(): InformDashboardAnalytics
    {
        return app(InformDashboardAnalytics::class);
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
            'from'     => $this->dt_init,
            'to'       => $this->dt_end,
            'company'  => $this->company,
            'search'   => $this->search,
            'returned' => $this->returned,
            'retry'    => $this->retry,
            'status'   => $this->status,
            'ads'      => $this->ads,
            'scope'    => $this->scope(),
        ];
    }

    /** @return array<string, mixed> */
    private function normalized(): array
    {
        return $this->analytics()->normalize($this->filters());
    }

    /** @return array<string, mixed> o mesmo filtro, só com tipos simples (vai para a fila) */
    private function exportParams(): array
    {
        return array_diff_key($this->filters(), ['scope' => 1]);
    }

    private function afterFilter(): void
    {
        $this->resetPage();
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => InformDashboardCharts::all($this->snapshot)]);
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
            '30d'   => [$today->copy()->subDays(29), $today],
            '90d'   => [$today->copy()->subDays(89), $today],
            'prev'  => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [$today->copy()->startOfMonth(), $today],
        };

        $this->preset  = array_key_exists($preset, self::PRESETS) ? $preset : 'month';
        $this->dt_init = $from->toDateString();
        $this->dt_end  = $to->toDateString();
    }
}
