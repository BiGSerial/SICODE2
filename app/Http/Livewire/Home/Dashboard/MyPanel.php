<?php

namespace App\Http\Livewire\Home\Dashboard;

use App\Jobs\Home\PersonalProductionsJob;
use App\Models\Notetimeline;
use App\Services\Home\MyPanel\{MyPanelAnalytics, MyPanelCharts};
use Carbon\Carbon;
use Livewire\Component;

/**
 * Home do usuário ("Meu painel"): o que está com ele, há quanto tempo, e a produtividade por atividade no período.
 * A tela só orquestra filtros; os números vêm de MyPanelAnalytics e os gráficos de MyPanelCharts.
 */
class MyPanel extends Component
{
    public const PRESETS = [
        '7d'    => 'Últimos 7 dias',
        '30d'   => 'Últimos 30 dias',
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
    ];

    /** Linhas da tabela "mais antigas" antes de expandir. */
    public const OLDEST_VISIBLE = 10;

    public const OLDEST_EXPANDED = 50;

    public ?string $selectedService = null;

    public string $preset = '30d';

    public ?string $dt_in = null;

    public ?string $dt_out = null;

    public bool $includeRi = false;

    public bool $showAllOldest = false;

    protected $queryString = [
        'selectedService' => ['except' => '', 'as' => 'servico'],
        'preset'          => ['except' => '30d', 'as' => 'periodo'],
        'dt_in'           => ['except' => '', 'as' => 'dtin'],
        'dt_out'          => ['except' => '', 'as' => 'dtout'],
        'includeRi'       => ['except' => false, 'as' => 'ri'],
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

    public function updatedSelectedService(): void
    {
        $this->selectedService = $this->selectedService ?: null;
        $this->refreshCharts();
    }

    public function updatedIncludeRi(): void
    {
        $this->refreshCharts();
    }

    public function exportToExcel(): void
    {
        PersonalProductionsJob::dispatch([
            'service'    => $this->selectedService ? [$this->selectedService] : [],
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
            'html'     => "<div class='card'><div class='card-body'><p>Seu painel está sendo gerado em Excel. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    /** @return array<string, mixed> resultado do MyPanelAnalytics, calculado uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        return app(MyPanelAnalytics::class)->snapshot((string) auth()->id(), $this->filters());
    }

    public function render()
    {
        $snapshot = $this->snapshot;
        $open     = $snapshot['open'];
        $limit    = $this->showAllOldest ? self::OLDEST_EXPANDED : self::OLDEST_VISIBLE;

        return view('livewire.home.dashboard.my-panel', [
            'snapshot'   => $snapshot,
            'charts'     => MyPanelCharts::all($snapshot),
            'oldest'     => array_slice($open, 0, $limit),
            'oldestMore' => max(0, count($open) - self::OLDEST_VISIBLE),
            'activities' => app(MyPanelAnalytics::class)->activities((string) auth()->id()),
            'recent'     => $this->recentActivity(),
            'firstName'  => $this->firstName(),
            'greeting'   => $this->greeting(),
        ]);
    }

    /** @return array{service: ?string, from: string, to: string, include_ri: bool} */
    private function filters(): array
    {
        return [
            'service'    => $this->selectedService,
            'from'       => $this->dt_in,
            'to'         => $this->dt_out,
            'include_ri' => $this->includeRi,
        ];
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
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => MyPanelCharts::all($this->snapshot)]);
    }

    private function recentActivity()
    {
        return Notetimeline::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->take(8)
            ->with(['Note:id,note', 'Service:id,uuid,service'])
            ->get();
    }

    private function firstName(): string
    {
        return (string) (explode(' ', trim((string) auth()->user()->name))[0] ?? '');
    }

    private function greeting(): string
    {
        $hour = now()->hour;

        return $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
    }
}
