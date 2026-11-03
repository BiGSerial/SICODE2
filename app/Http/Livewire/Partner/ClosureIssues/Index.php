<?php

namespace App\Http\Livewire\Partner\ClosureIssues;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, PartnerListToolkit};
use App\Models\{Centerjob};
use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosurePartnerSignal};
use App\Services\Closure\{ClosureOrderWorkflowException, ClosurePartnerSignalService};
use App\Services\PartnerAccess\PartnerAccessGate;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use AuthorizesPartnerAccess;
    use PartnerListToolkit;
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Situação da pendência (abas da lista); vazio = todas. */
    public string $status = '';

    public string $search = '';

    // Liberação central de depósito bloqueado (o bloqueio é confirmado pelo Despacho a partir do calendário)

    public ?int $releasingSignalId = null;

    public string $releaseNote = '';

    protected $queryString = [
        'status'  => ['except' => ''],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'filters' => ['except' => [], 'as' => 'f'],
        'page'    => ['except' => 1, 'as' => 'p'],
    ];

    /** Situações que viram aba (a pendência "Aberta" ainda não foi pedida à parceira). */
    public const STATUS_TABS = ['REQUESTED', 'RETURNED', 'RESPONDED', 'VALIDATING', 'RESOLVED'];

    public function mount(): void
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $this->status  = in_array($this->status, self::STATUS_TABS, true) ? $this->status : '';
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return array_filter([
            'company'  => count($this->companyFilterOptionsCached()) > 1 ? ['label' => 'Empresa'] : null,
            'category' => ['label' => 'Categoria'],
        ]);
    }

    protected function listFilterOptions(): array
    {
        return [
            'company'  => $this->companyFilterOptionsCached(),
            'category' => $this->rememberFilterOptions('category', fn () => ClosureIssueCategory::query()
                ->whereIn('id', $this->visibleIssues()->select('closure_issue_category_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'active'])
                ->map(fn ($category) => ['v' => (string) $category->id, 'l' => $category->name . (!$category->active ? ' (inativa)' : '')])
                ->all()),
        ];
    }

    private ?array $companyOptionsMemo = null;

    private function companyFilterOptionsCached(): array
    {
        return $this->companyOptionsMemo ??= $this->rememberFilterOptions('company', fn () => $this->companyFilterOptions());
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . auth()->id() . ':' . $this->partnerCompanyFilter;
    }

    protected function listFilterCacheTtl(): int
    {
        return 60;
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'search', 'partnerCompanyFilter'], true)) {
            $this->resetPage();
        }
    }

    public function setStatus(string $status): void
    {
        $this->status = in_array($status, self::STATUS_TABS, true) && $this->status !== $status ? $status : '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'partnerCompanyFilter');
        $this->clearListFilters();
    }

    // ------------------------------------------------ Depósito bloqueado (por empresa)

    public function startRelease(int $signalId): void
    {
        $this->authorizePartnerAccess('closure.signals.manage');
        $signal = $this->visibleSignals()->active()->whereKey($signalId)->firstOrFail();

        $this->releasingSignalId = $signal->id;
        $this->releaseNote       = '';
    }

    public function cancelRelease(): void
    {
        $this->releasingSignalId = null;
        $this->releaseNote       = '';
    }

    public function submitRelease(): void
    {
        $this->authorizePartnerAccess('closure.signals.manage');
        $signal = $this->visibleSignals()->active()->whereKey($this->releasingSignalId)->firstOrFail();

        try {
            $signal = app(ClosurePartnerSignalService::class)->releaseDeposit($signal, auth()->user(), $this->releaseNote);
        } catch (ClosureOrderWorkflowException $exception) {
            $this->cancelRelease();
            session()->flash('success', $exception->getMessage());

            return;
        }

        $this->cancelRelease();
        session()->flash('success', "Depósito liberado: {$signal->released_count} Ordem(ns) voltaram ao status anterior.");
    }

    /** Bloqueios dos depósitos cadastrados (centerjobs) para as empresas que o usuário enxerga. */
    private function visibleSignals()
    {
        $query = ClosurePartnerSignal::query()->with('Company');
        $user  = auth()->user();

        abort_unless($user, 403);

        $ids = $user->superadm ? null : collect(PartnerAccessGate::visibleCompanyIdsFor($user));

        if ($ids !== null && $ids->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        $selected = (string) ($this->partnerCompanyFilter ?? '');
        $scope    = $selected !== '' && ($ids === null || $ids->contains($selected)) ? [$selected] : ($ids?->all());

        if ($companies = $this->filterValues('company')) {
            $scope = $scope === null ? $companies : array_values(array_intersect(array_map('strval', $scope), $companies));
        }

        if ($scope !== null) {
            $query->where(fn ($q) => $q->whereIn('company_id', $scope)
                ->orWhereIn('deposit_code', Centerjob::query()->whereIn('company_id', $scope)->whereNotNull('deposit')->select('deposit')));
        } elseif ($selected !== '') {
            $query->where(fn ($q) => $q->where('company_id', $selected)
                ->orWhereIn('deposit_code', Centerjob::query()->where('company_id', $selected)->whereNotNull('deposit')->select('deposit')));
        }

        return $query;
    }

    public function render()
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $issues = $this->filteredIssues()
            ->with(['Order:id,ordem,note_id', 'Order.Note:id,note', 'Category:id,name', 'Subcategory:id,name'])
            ->withMax('Events as last_event_at', 'created_at')
            ->latest('updated_at')
            ->paginate(25);

        $counts = $this->visibleIssues()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $signals = $this->visibleSignals()->active()->latest()->get();

        return view('livewire.partner.closure-issues.index', [
            'issues'      => $issues,
            'counts'      => $counts,
            'signals'     => $signals,
            'hasCriteria' => $this->hasListFilters() || trim($this->search) !== '' || $this->status !== '' || (string) $this->partnerCompanyFilter !== '',
            'canRelease'  => PartnerAccessGate::allows(auth()->user(), 'closure.signals.manage'),
            'canRespond'  => PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'),
        ]);
    }

    /** Pendências da empresa com os filtros da tela (a mesma consulta alimenta a lista e a exportação). */
    private function filteredIssues()
    {
        return $this->applyCompanyFilter($this->visibleIssues(), 'destination_company_id')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->filterValues('category'), fn ($query, $ids) => $query->whereIn('closure_issue_category_id', $ids))
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($query) use ($search) {
                    $query->whereHas('Order', fn ($order) => $order->where('ordem', 'like', "%{$search}%"))
                        ->orWhereHas('Order.Note', fn ($note) => $note->where('note', 'like', "%{$search}%"));
                });
            });
    }

    /** Consulta completa (sem paginar) para a exportação: usada pelo ExportPartnerClosureListJob. */
    public function exportQuery()
    {
        $this->authorizePartnerAccess('closure.issues.list');

        return $this->filteredIssues()->with(['Order.Note', 'Category', 'Subcategory', 'Events'])->latest('updated_at');
    }

    public function exportToExcel(): void
    {
        $this->authorizePartnerAccess('closure.issues.list');

        \App\Jobs\Closure\ExportPartnerClosureListJob::dispatch(static::class, [
            'search'               => $this->search,
            'status'               => $this->status,
            'filters'              => $this->filters,
            'partnerCompanyFilter' => (string) $this->partnerCompanyFilter,
        ], (string) auth()->id());

        $this->dispatchBrowserEvent('closure:toast', ['message' => 'Exportação iniciada. Você será avisado quando o arquivo estiver pronto.', 'type' => 'info']);
    }

    private function visibleIssues()
    {
        $query = ClosureIssue::query();
        $this->applyPartnerCompanyScope($query, 'destination_company_id');

        return $query;
    }
}
