<?php

namespace App\Http\Livewire\Partner\ClosureIssues;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\{Centerjob};
use App\Models\Closure\{ClosureIssue, ClosurePartnerSignal};
use App\Services\Closure\{ClosureOrderWorkflowException, ClosurePartnerSignalService};
use App\Services\PartnerAccess\PartnerAccessGate;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $status = '';

    public string $categoryId = '';

    public string $search = '';

    // Liberação central de depósito bloqueado (o bloqueio é confirmado pelo Despacho a partir do calendário)

    public ?int $releasingSignalId = null;

    public string $releaseNote = '';

    protected $queryString = [
        'status'     => ['except' => ''],
        'categoryId' => ['except' => '', 'as' => 'categoria'],
        'search'     => ['except' => '', 'as' => 'buscar'],
        'page'       => ['except' => 1, 'as' => 'p'],
    ];

    public function mount(): void
    {
        $this->authorizePartnerAccess('closure.issues.list');
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'categoryId', 'search', 'partnerCompanyFilter'], true)) {
            $this->resetPage();
        }
    }

    public function removeFilter(string $kind): void
    {
        match ($kind) {
            'search'   => $this->search               = '',
            'status'   => $this->status               = '',
            'category' => $this->categoryId           = '',
            'company'  => $this->partnerCompanyFilter = '',
            default    => null,
        };
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'categoryId', 'partnerCompanyFilter');
        $this->resetPage();
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
            ->with(['Order.Note', 'Category', 'Subcategory', 'Events.Actor'])
            ->latest('updated_at')
            ->paginate(25);

        $categories = $this->visibleIssues()
            ->with('Category:id,name,active')
            ->get(['closure_issue_category_id'])
            ->pluck('Category')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $counts = $this->visibleIssues()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $signals = $this->visibleSignals()->active()->latest()->get();

        return view('livewire.partner.closure-issues.index', [
            'issues'     => $issues,
            'categories' => $categories,
            'counts'     => $counts,
            'signals'    => $signals,
            'canRelease' => PartnerAccessGate::allows(auth()->user(), 'closure.signals.manage'),
            'canRespond' => PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'),
        ]);
    }

    /** Pendências da empresa com os filtros da tela (a mesma consulta alimenta a lista e a exportação). */
    private function filteredIssues()
    {
        return $this->visibleIssues()
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->categoryId !== '', fn ($query) => $query->where('closure_issue_category_id', $this->categoryId))
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
            'categoryId'           => $this->categoryId,
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
