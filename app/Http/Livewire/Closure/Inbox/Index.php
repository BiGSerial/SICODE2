<?php

namespace App\Http\Livewire\Closure\Inbox;

use App\Http\Livewire\Closure\Concerns\WithBulkSearch;
use App\Models\Closure\ClosureOrderState;
use App\Services\Closure\{ClosureArea};
use App\Services\Closure\Queries\{ClosureDispatchListQuery, ClosureInboxQuery};
use Livewire\{Component, WithPagination};

/**
 * Caixa de Medição / Fiscalização dentro da atividade.
 *  - "Em acompanhamento": só visão. Ordens do Encerramento no status usuário mapeado para a área (ClosureStatusUserRouteService);
 *    saem sozinhas quando o status muda.
 *  - "Despachadas": pendências que o Encerramento enviou e esperam a área (ação, Fase 3).
 */
class Index extends Component
{
    use WithBulkSearch;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $area = ClosureArea::MEASUREMENT;

    public string $tab = 'accompanying';

    public string $search = '';

    public string $type = 'all';

    /** @var array<int, string> */
    public array $prefixes = [];

    public string $issueStatus = 'open';

    public ?string $holderUserId = null;

    /** Empresa do informe da Ordem (mesmo filtro "Empresa" do Despacho). */
    public ?string $companyId = null;

    public function mount(string $area, string $tab = 'accompanying'): void
    {
        abort_unless(ClosureArea::hasInbox($area), 404);
        abort_unless(auth()->user()?->can('closure.inbox.' . strtolower($area)), 403);

        $this->area = $area;
        $this->tab  = in_array($tab, ['accompanying', 'dispatched'], true) ? $tab : 'accompanying';
    }

    public function render()
    {
        $query  = app(ClosureInboxQuery::class);
        $counts = $query->accompanyingCounts($this->area);

        $accompanying = $this->tab === 'accompanying' ? $query->accompanying($this->area, $this->filters(), 25, $this->page) : null;
        $issues       = $this->tab === 'dispatched' ? $query->dispatched($this->area, $this->issueStatus)->paginate(25) : null;
        $stateIds     = $accompanying ? $accompanying->getCollection()->pluck('state_id')->filter()->all() : [];

        return view('livewire.closure.inbox.index', [
            'areaLabel'    => ClosureArea::label($this->area),
            'counts'       => $counts,
            'totalWatched' => array_sum($counts),
            'dispatched'   => $query->dispatched($this->area, 'open')->count(),
            'mapped'       => array_keys($counts),
            'accompanying' => $accompanying,
            'issues'       => $issues,
            // "No status desde" só existe para Ordem já despachada (o rastreio é por closure_order_states).
            'since'        => $stateIds === [] ? collect() : ClosureOrderState::query()->whereIn('id', $stateIds)->get(['id', 'status_user_changed_at', 'status_user_estimated'])->keyBy('id'),
            'holders'      => $this->tab === 'accompanying' ? $query->filterOptions($this->area, $this->filters()) : ['users' => collect(), 'companies' => collect()],
            'canConfigure' => auth()->user()?->can('closure.manage'),
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['accompanying', 'dispatched'], true) ? $tab : 'accompanying';
        $this->resetPage();
    }

    public function togglePrefix(string $prefix): void
    {
        $this->prefixes = in_array($prefix, $this->prefixes, true)
            ? array_values(array_diff($this->prefixes, [$prefix]))
            : array_values(array_merge($this->prefixes, [$prefix]));
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search       = '';
        $this->type         = 'all';
        $this->prefixes     = [];
        $this->holderUserId = null;
        $this->companyId    = null;
        $this->bulkTerms    = [];
        $this->bulkText     = '';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedHolderUserId(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyId(): void
    {
        $this->resetPage();
    }

    public function updatedIssueStatus(): void
    {
        $this->resetPage();
    }

    public function exportList(): void
    {
        // Mesmo recorte da tela, no formato de filtros da lista do Despacho (ExportClosureListJob).
        \App\Jobs\Closure\ExportClosureListJob::dispatch(static::class, ['area' => $this->area] + app(ClosureInboxQuery::class)->listFilters($this->area, $this->filters()), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'success', 'title' => 'Exportação iniciada', 'html' => '<p class="mb-0"><strong>Você será notificado quando o download estiver disponível.</strong></p>', 'timer' => 5000]);
    }

    /** Ordens e Notas que a lista devolve com os filtros atuais (base do resumo da busca em massa). */
    protected function bulkSearchedValues(): array
    {
        $ids = app(ClosureInboxQuery::class)->accompanyingIds($this->area, $this->filters());

        return collect(app(ClosureDispatchListQuery::class)->briefs($ids))->flatMap(fn (array $brief) => [$brief['ordem'] ?? null, $brief['note'] ?? null])->filter()->all();
    }

    /** @return array<string, mixed> */
    private function filters(): array
    {
        return [
            'search'       => $this->search,
            'type'         => $this->type,
            'prefixes'     => $this->prefixes,
            'bulk'         => $this->bulkTerms,
            'holderUserId' => $this->holderUserId ?: null,
            'companyId'    => $this->companyId ?: null,
        ];
    }
}
