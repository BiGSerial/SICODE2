<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalDemand;
use App\Models\User;
use App\Services\Legal\LegalDemandBulkService;
use Livewire\{Component, WithPagination};

class DemandQueue extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filtros
    public string $search = '';

    public string $tab = 'all';

    public string $sourceType = '';

    public string $statusFilter = '';

    public string $dueDateFilter = '';

    public string $controllerFilter = '';

    public string $sortBy = 'source_due_at';

    public string $sortDir = 'asc';

    public int    $perPage = 25;

    // Seleção em lote
    public array $selectedIds = [];

    public bool  $selectAll = false;

    // Modal: Enviar para Campo em lote
    public bool   $showBulkFieldModal = false;

    public string $bulkAssignToUserId = '';

    public string $bulkAssignToTeamId = '';

    public string $bulkAssignMessage = '';

    public string $bulkAssignDueAt = '';

    // Modal: Reassinar Controlador em lote
    public bool   $showBulkReassignModal = false;

    public string $bulkNewControllerId = '';

    // Modal: Ignorar em lote
    public bool   $showBulkIgnoreModal = false;

    public string $bulkIgnoreReason = '';

    // Modal: Transferência X→Y
    public bool   $showTransferModal = false;

    public string $transferFromUserId = '';

    public string $transferToUserId = '';

    public int    $transferPreviewCount = 0;

    protected $queryString = [
        'tab'          => ['except' => 'all'],
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sourceType'   => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('legal.demands.triage'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedTab(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatedSourceType(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $field;
            $this->sortDir = 'asc';
        }
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll   = false;
    }

    public function updatedTransferFromUserId(): void
    {
        if ($this->transferFromUserId) {
            $this->transferPreviewCount = LegalDemand::where('controller_user_id', $this->transferFromUserId)
                ->whereNotIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])
                ->count();
        } else {
            $this->transferPreviewCount = 0;
        }
    }

    public function transferController(): void
    {
        $this->validate([
            'transferFromUserId' => 'required|different:transferToUserId',
            'transferToUserId'   => 'required',
        ]);

        $result = app(LegalDemandBulkService::class)->transferAllFromUser(
            fromUser: User::findOrFail($this->transferFromUserId),
            toUser:   User::findOrFail($this->transferToUserId),
            actor:    auth()->user(),
        );

        $this->showTransferModal  = false;
        $this->transferFromUserId = '';
        $this->transferToUserId   = '';

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Transferência concluída',
            'html'  => "{$result->applied} demandas transferidas. {$result->skipped} ignoradas.",
        ]);
    }

    public function sendBatchToField(): void
    {
        $this->validate([
            'bulkAssignToUserId' => 'required_without:bulkAssignToTeamId',
        ]);

        $result = app(LegalDemandBulkService::class)->sendBatchToField(
            demandIds: $this->selectedIds,
            actor:     auth()->user(),
            toUserId:  $this->bulkAssignToUserId ? (int) $this->bulkAssignToUserId : null,
            toTeamId:  $this->bulkAssignToTeamId ?: null,
            message:   $this->bulkAssignMessage ?: null,
            dueAt:     $this->bulkAssignDueAt ?: null,
        );

        $this->showBulkFieldModal = false;
        $this->clearSelection();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Envio concluído',
            'html'  => "{$result->applied} enviadas. {$result->skipped} ignoradas por status incompatível.",
        ]);
    }

    public function reassignControllerBatch(): void
    {
        $this->validate(['bulkNewControllerId' => 'required']);

        $result = app(LegalDemandBulkService::class)->reassignController(
            demandIds:     $this->selectedIds,
            newController: User::findOrFail($this->bulkNewControllerId),
            actor:         auth()->user(),
        );

        $this->showBulkReassignModal = false;
        $this->clearSelection();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Reatribuição concluída',
            'html'  => "{$result->applied} demandas atualizadas.",
        ]);
    }

    public function ignoreBatch(): void
    {
        $this->validate(['bulkIgnoreReason' => 'required|min:5']);

        $result = app(LegalDemandBulkService::class)->ignoreBatch(
            demandIds: $this->selectedIds,
            actor:     auth()->user(),
            reason:    $this->bulkIgnoreReason,
        );

        $this->showBulkIgnoreModal = false;
        $this->clearSelection();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Concluído',
            'html'  => "{$result->applied} demandas ignoradas.",
        ]);
    }

    private function baseQuery()
    {
        $query = LegalDemand::query()
            ->with(['legalCase', 'controller', 'currentAssignee']);

        // Tab filters — active tabs exclude externally-closed demands; "closed" tab includes them
        match ($this->tab) {
            'triage'   => $query->externallyActive()->whereIn('internal_status', ['new_imported', 'triage', 'waiting_controller_action']),
            'in_field' => $query->externallyActive()->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response']),
            'returned' => $query->externallyActive()->whereIn('internal_status', ['returned_by_field', 'under_controller_review', 'returned_for_correction']),
            'overdue'  => $query->externallyActive()->overdue(),
            'closed'   => $query->where(function ($q) {
                $q->whereIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])
                    ->orWhere(fn ($sub) => $sub->externallyClosed());
            }),
            default => $query->externallyActive()->whereNotIn('internal_status', ['cancelled', 'ignored']),
        };

        if ($this->search) {
            $s = "%{$this->search}%";
            $query->where(
                fn ($q) => $q
                ->where('source_case_number', 'like', $s)
                ->orWhere('source_process_number', 'like', $s)
                ->orWhere('title', 'like', $s)
                ->orWhere('opposing_party', 'like', $s)
            );
        }

        if ($this->statusFilter) {
            $query->where('internal_status', $this->statusFilter);
        }

        if ($this->sourceType) {
            $query->where('source_type', $this->sourceType);
        }

        if ($this->controllerFilter) {
            $query->where('controller_user_id', $this->controllerFilter);
        }

        if ($this->dueDateFilter === 'overdue') {
            $query->overdue();
        } elseif ($this->dueDateFilter === '3days') {
            $query->whereBetween('source_due_at', [now(), now()->addDays(3)]);
        } elseif ($this->dueDateFilter === '7days') {
            $query->whereBetween('source_due_at', [now(), now()->addDays(7)]);
        } elseif ($this->dueDateFilter === 'no_date') {
            $query->whereNull('source_due_at');
        }

        $query->orderByRaw('ISNULL(source_due_at) ASC')
              ->orderBy('source_due_at', $this->sortDir);

        return $query;
    }

    public function render()
    {
        $demands     = $this->baseQuery()->paginate($this->perPage);
        $controllers = User::whereIn(
            'id',
            LegalDemand::externallyActive()
                ->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->pluck('controller_user_id')
                ->filter()
                ->unique()
        )->orderBy('name')->get();

        $fieldUsers = User::orderBy('name')->get();

        return view('livewire.legal.controller.demand-queue', [
            'demands'     => $demands,
            'controllers' => $controllers,
            'fieldUsers'  => $fieldUsers,
        ]);
    }
}
