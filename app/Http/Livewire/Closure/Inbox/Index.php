<?php

namespace App\Http\Livewire\Closure\Inbox;

use App\Http\Livewire\Closure\Concerns\WithBulkSearch;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\Closure\{ClosureIssue, ClosureOrderState};
use App\Services\Closure\{ClosureArea, ClosureEventLabels, ClosureInboxPaymentContext, ClosureInboxRowPresenter, ClosureIssueWorkflowException, ClosureIssueWorkflowService};
use App\Services\Closure\Queries\{ClosureDispatchListQuery, ClosureInboxQuery};
use Livewire\{Component, WithPagination};

/**
 * Caixa de Medição / Fiscalização dentro da atividade. Duas abas de natureza diferente (a tela diz qual é qual):
 *  - "Em acompanhamento" (só visão): Ordens do Encerramento no status usuário mapeado para a área (ClosureStatusUserRouteService);
 *    nada a responder, saem sozinhas quando o status muda.
 *  - "Despachos para retorno" (exige resposta): pendências que o Encerramento enviou e esperam a área (Fase 3).
 *
 * A caixa é autônoma: não leva ao Encerramento (nem a rotas `closure.*`). O detalhe da Ordem é só leitura, dentro da própria tela, e o
 * despacho para retorno tem fluxo próprio de resolução: a área lê a solicitação e a finaliza com um retorno (`respondByArea`).
 *
 * Padrão de lista operacional (docs/PADRAO_LISTAS_OPERACIONAIS.md): filtros pelo `WithListFilters` (na URL), busca com debounce,
 * linha pronta no `ClosureInboxRowPresenter`, selos e datas pelos `<x-ops.*>`. Lista de controle: não despacha, só acompanha.
 */
class Index extends Component
{
    use WithBulkSearch;
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $area = ClosureArea::MEASUREMENT;

    public string $tab = 'accompanying';

    public int $perPage = 25;

    public string $search = '';

    public string $type = 'all';

    public string $issueStatus = 'open';

    /** Ordem aberta no detalhe só de leitura (id da Ordem). */
    public ?int $detailOrderId = null;

    /** Pendência aberta no modal de resolução (id) e o retorno que a área está escrevendo. */
    public ?int $issueId = null;

    public string $issueReply = '';

    protected $queryString = [
        'search'      => ['except' => '', 'as' => 'buscar'],
        'type'        => ['except' => 'all', 'as' => 'tipo'],
        'issueStatus' => ['except' => 'open', 'as' => 'situacao'],
        'filters'     => ['except' => [], 'as' => 'f'],
    ];

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

        $accompanying = $this->tab === 'accompanying' ? $query->accompanying($this->area, $this->filters(), $this->perPage, $this->page) : null;
        $issues       = $this->tab === 'dispatched' ? $query->dispatched($this->area, $this->issueStatus)->paginate($this->perPage) : null;
        $stateIds     = $accompanying ? $accompanying->getCollection()->pluck('state_id')->filter()->all() : [];

        // "No status desde" só existe para Ordem já despachada (o rastreio é por closure_order_states).
        $since     = $stateIds === [] ? collect() : ClosureOrderState::query()->whereIn('id', $stateIds)->get(['id', 'status_user_changed_at', 'status_user_estimated'])->keyBy('id');
        $presenter = app(ClosureInboxRowPresenter::class);

        // Medição: o que o usuário de Pagamento acompanha (fila de Pagamento, informe, Valor ADS, prazo de medição), por Nota da página.
        $payment = $accompanying && $this->area === ClosureArea::MEASUREMENT
            ? app(ClosureInboxPaymentContext::class)->forNotes($accompanying->getCollection()->pluck('note_id')->all())
            : [];

        $rows = $accompanying ? $accompanying->getCollection()->map(fn ($order) => $presenter->present($order, $order->state_id ? $since->get($order->state_id) : null) + ['payment' => $payment[(int) $order->note_id] ?? null]) : collect();

        return view('livewire.closure.inbox.index', [
            'areaLabel'    => ClosureArea::label($this->area),
            'counts'       => $counts,
            'totalWatched' => array_sum($counts),
            'dispatched'   => $query->dispatched($this->area, 'open')->count(),
            'mapped'       => array_keys($counts),
            'accompanying' => $accompanying,
            'rows'         => $rows,
            'detail'       => $this->detailOrderId ? $rows->firstWhere('id', $this->detailOrderId) : null,
            'issueDetail'  => $this->issueId ? $this->issueDetail($presenter) : null,
            'canRespond'   => (bool) auth()->user()?->can('closure.inbox.' . strtolower($this->area) . '.respond'),
            'paymentView'  => $this->area === ClosureArea::MEASUREMENT,
            'issues'       => $issues,
            'issueRows'    => $issues ? $issues->getCollection()->map(fn ($issue) => $presenter->issue($issue)) : collect(),
            'hasCriteria'  => $this->hasCriteria(),
        ]);
    }

    public function openDetail(int $orderId): void
    {
        $this->detailOrderId = $orderId;
    }

    public function closeDetail(): void
    {
        $this->detailOrderId = null;
    }

    public function openIssue(int $issueId): void
    {
        if ($this->ownIssue($issueId)) {
            $this->issueId    = $issueId;
            $this->issueReply = '';
            $this->resetErrorBag();
        }
    }

    public function closeIssue(): void
    {
        $this->issueId    = null;
        $this->issueReply = '';
        $this->resetErrorBag();
    }

    /** Finaliza a solicitação: grava o retorno da área e devolve a pendência ao Encerramento para validar. */
    public function finalizeIssue(): void
    {
        abort_unless(auth()->user()?->can('closure.inbox.' . strtolower($this->area) . '.respond'), 403);

        $issue = $this->issueId ? $this->ownIssue($this->issueId) : null;

        if (!$issue) {
            $this->closeIssue();

            return;
        }

        try {
            app(ClosureIssueWorkflowService::class)->respondByArea($issue, auth()->user(), $this->issueReply);
        } catch (ClosureIssueWorkflowException $e) {
            $this->addError('issueReply', $e->getMessage());

            return;
        }

        $this->closeIssue();
        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'success', 'title' => 'Solicitação finalizada', 'html' => '<p class="mb-0">O retorno foi enviado ao Encerramento para validação.</p>', 'timer' => 3500]);
    }

    /** Só pendências destinadas a esta caixa: o id vem do navegador e nunca é confiável sozinho. */
    private function ownIssue(int $issueId): ?ClosureIssue
    {
        return ClosureIssue::query()->whereKey($issueId)->where('destination_area', $this->area)->first();
    }

    /** @return array<string, mixed>|null */
    private function issueDetail(ClosureInboxRowPresenter $presenter): ?array
    {
        $issue = ClosureIssue::query()->whereKey($this->issueId)->where('destination_area', $this->area)
            ->with(['Order.Note', 'Category', 'Subcategory', 'OpenedBy', 'Events.Actor'])->first();

        if (!$issue) {
            return null;
        }

        return $presenter->issue($issue) + [
            'description'  => $issue->description,
            'requirements' => array_values((array) ($issue->requirements ?? [])),
            'openedBy'     => $issue->OpenedBy?->name,
            'canFinalize'  => in_array($issue->status, [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED], true),
            'timeline'     => $issue->Events->map(fn ($event) => [
                'label' => ClosureEventLabels::issueLabel($event->type),
                'at'    => $event->created_at,
                'by'    => $event->Actor?->name,
                'tone'  => ClosureEventLabels::issueTone($event->type),
                'note'  => $event->meta['message'] ?? null,
            ])->all(),
        ];
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || $this->search !== '' || $this->type !== 'all' || $this->bulkTerms !== [];
    }

    /** Filtros da lista (na URL): status usuário, responsável no Encerramento e empresa do informe. */
    protected function listFilterDefinitions(): array
    {
        return [
            'status'      => ['label' => 'Status usuário'],
            'responsavel' => ['label' => 'Responsável (Encerramento)'],
            'empresa'     => ['label' => 'Empresa (informe)'],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . $this->area;
    }

    protected function listFilterOptions(): array
    {
        $query = app(ClosureInboxQuery::class);

        return [
            'status' => $this->rememberFilterOptions('status', fn () => array_map(
                fn ($prefix) => ['v' => (string) $prefix, 'l' => (string) $prefix],
                array_keys($query->accompanyingCounts($this->area)),
            )),
            'responsavel' => $this->rememberFilterOptions('responsavel', fn () => $query->filterOptions($this->area)['users']
                ->map(fn ($user) => ['v' => (string) $user->id, 'l' => (string) $user->name])->values()->all()),
            'empresa' => $this->rememberFilterOptions('empresa', fn () => $query->filterOptions($this->area)['companies']
                ->map(fn ($company) => ['v' => (string) $company->id, 'l' => (string) $company->name])->values()->all()),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['accompanying', 'dispatched'], true) ? $tab : 'accompanying';
        $this->resetPage();
    }

    /** Cartão de status: liga/desliga o status no filtro (mesmo `status` do painel de filtros e da URL). */
    public function togglePrefix(string $prefix): void
    {
        $current = $this->filterValues('status');

        $this->filters['status'] = in_array($prefix, $current, true)
            ? array_values(array_diff($current, [$prefix]))
            : [...$current, $prefix];

        if (!$this->filters['status']) {
            unset($this->filters['status']);
        }

        $this->afterListFiltersChanged();
    }

    /** "Limpar filtros": filtros, busca (simples e em massa) e tipo. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search    = '';
        $this->type      = 'all';
        $this->bulkTerms = [];
        $this->bulkText  = '';
        $this->filters   = [];

        $this->afterListFiltersChanged();
    }

    public function clearFilters(): void
    {
        $this->clearAllCriteria();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedIssueStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
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
            'search'        => $this->search,
            'type'          => $this->type,
            'prefixes'      => $this->filterValues('status'),
            'bulk'          => $this->bulkTerms,
            'holderUserIds' => $this->filterValues('responsavel'),
            'companyIds'    => $this->filterValues('empresa'),
        ];
    }
}
