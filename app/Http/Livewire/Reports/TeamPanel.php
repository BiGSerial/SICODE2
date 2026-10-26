<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\TeamPanelExportJob;
use App\Services\Reports\TeamPanel\{TeamPanelAnalytics, TeamPanelCharts};
use Carbon\Carbon;
use Livewire\Component;

/**
 * Painel da equipe (Serviços → Relatórios, gerência): o "Meu painel" sobre todos os usuários, filtrável por empresa, atividade e pessoa.
 * A tela só orquestra filtros; os números vêm de TeamPanelAnalytics e os gráficos de TeamPanelCharts (mesmo `my-panel.js`).
 */
class TeamPanel extends Component
{
    public const PRESETS = [
        '7d'    => 'Últimos 7 dias',
        '30d'   => 'Últimos 30 dias',
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
    ];

    public const OLDEST_VISIBLE = 10;

    public const OLDEST_EXPANDED = 50;

    public const USERS_VISIBLE = 12;

    public const USERS_EXPANDED = 60;

    public ?string $selectedCompany = null;

    public ?string $selectedService = null;

    public ?string $selectedUser = null;

    public string $preset = '30d';

    public ?string $dt_in = null;

    public ?string $dt_out = null;

    public bool $includeRi = false;

    /**
     * Partes do painel já entregues (`TeamPanelAnalytics::PARTS`). A tela abre na hora e `loadNext` (um por resposta, encadeado
     * pelo wire:init da view) vai liberando uma parte por vez: o que ainda não chegou aparece como esqueleto.
     *
     * @var array<string, bool>
     */
    public array $loaded = ['open' => false, 'daily' => false, 'production' => false, 'monthly' => false];

    public bool $showAllOldest = false;

    public bool $showAllUsers = false;

    protected $queryString = [
        'selectedCompany' => ['except' => '', 'as' => 'empresa'],
        'selectedService' => ['except' => '', 'as' => 'servico'],
        'selectedUser'    => ['except' => '', 'as' => 'usuario'],
        'preset'          => ['except' => '30d', 'as' => 'periodo'],
        'dt_in'           => ['except' => '', 'as' => 'dtin'],
        'dt_out'          => ['except' => '', 'as' => 'dtout'],
        'includeRi'       => ['except' => false, 'as' => 'ri'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('management'), 403);

        Carbon::setLocale('pt_BR');

        if ($this->preset !== 'custom') {
            $this->applyPreset($this->preset);
        }
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
        abort_unless(auth()->user()?->can('management'), 403);
    }

    /** Libera a próxima parte ainda não carregada (ordem de `TeamPanelAnalytics::PARTS`). */
    public function loadNext(): void
    {
        foreach (TeamPanelAnalytics::PARTS as $part) {
            if (!($this->loaded[$part] ?? false)) {
                $this->loaded[$part] = true;

                return;
            }
        }
    }

    /** Partes que já podem ser calculadas (as demais ficam vazias na tela, como esqueleto). */
    private function loadedParts(): array
    {
        return array_values(array_filter(TeamPanelAnalytics::PARTS, fn (string $part) => $this->loaded[$part] ?? false));
    }

    public function setPreset(string $preset): void
    {
        $this->applyPreset($preset);
        $this->refreshCharts();
    }

    public function updatedDtIn(): void
    {
        $this->preset = 'custom';
        $this->refreshCharts();
    }

    public function updatedDtOut(): void
    {
        $this->preset = 'custom';
        $this->refreshCharts();
    }

    public function updatedSelectedCompany(): void
    {
        $this->selectedCompany = $this->selectedCompany ?: null;
        $this->pruneDependentFilters();
        $this->refreshCharts();
    }

    public function updatedSelectedService(): void
    {
        $this->selectedService = $this->selectedService ?: null;
        $this->pruneDependentFilters();
        $this->refreshCharts();
    }

    public function updatedSelectedUser(): void
    {
        $this->selectedUser = $this->selectedUser ?: null;
        $this->refreshCharts();
    }

    public function updatedIncludeRi(): void
    {
        $this->refreshCharts();
    }

    /** Clique numa pessoa ou empresa das tabelas: filtra o painel inteiro nela. */
    public function focusUser(string $userId): void
    {
        $this->selectedUser = $userId;
        $this->refreshCharts();
    }

    public function focusCompany(string $companyId): void
    {
        $this->selectedCompany = $companyId;
        $this->pruneDependentFilters();
        $this->refreshCharts();
    }

    public function clearFilters(): void
    {
        $this->selectedCompany = $this->selectedService = $this->selectedUser = null;
        $this->refreshCharts();
    }

    public function exportToExcel(): void
    {
        TeamPanelExportJob::dispatch([
            'company'    => $this->selectedCompany,
            'service'    => $this->selectedService,
            'user'       => $this->selectedUser,
            'dt_init'    => $this->filters()['from'],
            'dt_end'     => $this->filters()['to'],
            'include_ri' => $this->includeRi,
        ], auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O painel da equipe está sendo gerado em Excel. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    /** @return array<string, mixed> resultado do TeamPanelAnalytics, calculado uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        $analytics = app(TeamPanelAnalytics::class);
        $now       = now();

        return $analytics->assemble($analytics->normalize($this->filters(), $now), $now, $this->loadedParts(), cache: true);
    }

    public function render()
    {
        $analytics = app(TeamPanelAnalytics::class);

        $snapshot = $this->snapshot;
        $open     = $snapshot['open'];
        $limit    = $this->showAllOldest ? self::OLDEST_EXPANDED : self::OLDEST_VISIBLE;
        $users    = $this->showAllUsers ? self::USERS_EXPANDED : self::USERS_VISIBLE;

        return view('livewire.reports.team-panel', [
            'snapshot'     => $snapshot,
            'nextPart'     => collect(TeamPanelAnalytics::PARTS)->first(fn (string $part) => !($this->loaded[$part] ?? false)),
            'charts'       => TeamPanelCharts::all($snapshot),
            'oldest'       => array_slice($open, 0, $limit),
            'oldestMore'   => max(0, count($open) - self::OLDEST_VISIBLE),
            'userRows'     => array_slice($snapshot['users'], 0, $users),
            'usersMore'    => max(0, count($snapshot['users']) - self::USERS_VISIBLE),
            'companyList'  => $analytics->companies(),
            'activityList' => $analytics->activities($this->selectedCompany),
            'peopleList'   => $analytics->people($this->selectedCompany, $this->selectedService),
            'hasFilters'   => (bool) ($this->selectedCompany || $this->selectedService || $this->selectedUser),
            'greeting'     => now()->translatedFormat('l, d \d\e F'),
        ]);
    }

    /** @return array{company: ?string, service: ?string, user: ?string, from: ?string, to: ?string, include_ri: bool} */
    private function filters(): array
    {
        return [
            'company'    => $this->selectedCompany,
            'service'    => $this->selectedService,
            'user'       => $this->selectedUser,
            'from'       => $this->dt_in,
            'to'         => $this->dt_out,
            'include_ri' => $this->includeRi,
        ];
    }

    /** Ao trocar empresa/atividade, tira o que deixou de existir naquele recorte (ex.: pessoa de outra empresa). */
    private function pruneDependentFilters(): void
    {
        $analytics = app(TeamPanelAnalytics::class);

        if ($this->selectedService && !$analytics->activities($this->selectedCompany)->contains('uuid', $this->selectedService)) {
            $this->selectedService = null;
        }

        if ($this->selectedUser && !$analytics->people($this->selectedCompany, $this->selectedService)->contains('id', $this->selectedUser)) {
            $this->selectedUser = null;
        }
    }

    private function applyPreset(string $preset): void
    {
        $today = now();

        [$from, $to] = match ($preset) {
            '7d'    => [$today->copy()->subDays(6), $today],
            'month' => [$today->copy()->startOfMonth(), $today],
            'prev'  => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [$today->copy()->subDays(29), $today],
        };

        $this->preset = array_key_exists($preset, self::PRESETS) ? $preset : '30d';
        $this->dt_in  = $from->toDateString();
        $this->dt_out = $to->toDateString();
    }

    /** Envia os gráficos novos; o JS atualiza cada dataset in-place (sem recriar o gráfico). */
    private function refreshCharts(): void
    {
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => TeamPanelCharts::all($this->snapshot)]);
    }
}
