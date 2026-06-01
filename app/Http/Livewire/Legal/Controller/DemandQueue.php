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
    public bool   $groupByCase = false;

    // Seleção em lote
    public array $selectedIds = [];

    public bool  $selectAll = false;

    // Modal: Reassinar Controlador em lote
    public bool   $showBulkReassignModal = false;

    public string $bulkNewControllerId = '';

    // Modal: Ignorar em lote
    public bool   $showBulkIgnoreModal = false;

    public string $bulkIgnoreReason = '';

    // Modal: Encerrar em lote
    public bool   $showBulkCloseModal = false;

    public string $bulkCloseReason = '';

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
        'groupByCase'  => ['except' => false],
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

    public function closeInternalBatch(): void
    {
        $this->validate(['bulkCloseReason' => 'required|min:5']);

        $result = app(LegalDemandBulkService::class)->closeInternalBatch(
            demandIds: $this->selectedIds,
            actor: auth()->user(),
            reason: $this->bulkCloseReason,
        );

        $this->showBulkCloseModal = false;
        $this->bulkCloseReason = '';
        $this->clearSelection();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Encerramento em massa concluído',
            'html'  => "{$result->applied} demandas encerradas. {$result->skipped} ignoradas.",
        ]);
    }

    private function baseQuery()
    {
        $query = LegalDemand::query()
            
            ->with(['legalCase', 'controller', 'currentAssignee']);

        // Tab filters — active tabs exclude externally-closed demands; "closed" tab includes them
        match ($this->tab) {
            'triage'   => $query->externallyActive()
                ->whereIn('internal_status', ['new_imported', 'triage', 'waiting_controller_action'])
                ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%'])
                ->whereNull('current_assigned_user_id')
                ->whereNull('current_assigned_team_id')
                ->whereDoesntHave('assignments', function ($aq) {
                    $aq->whereIn('status', ['sent', 'received', 'returned_for_correction']);
                }),
            'in_progress' => $query->externallyActive()->whereIn('internal_status', [
                'sent_to_field',
                'field_received',
                'waiting_field_response',
                'returned_by_field',
                'under_controller_review',
                'ready_to_close_external',
                'reopened',
            ]),
            'overdue'  => $query->externallyActive()
                ->overdue()
                ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%']),
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
                ->orWhere('source_subject', 'like', $s)
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
            $query->overdue()
                ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%']);
        } elseif ($this->dueDateFilter === '3days') {
            $query->whereBetween('source_due_at', [now(), now()->addDays(3)]);
        } elseif ($this->dueDateFilter === '7days') {
            $query->whereBetween('source_due_at', [now(), now()->addDays(7)]);
        } elseif ($this->dueDateFilter === 'no_date') {
            $query->whereNull('source_due_at');
        }

        $query->orderByRaw("
                CASE
                    WHEN LOWER(COALESCE(process_status_at_import, '')) LIKE '%encerrad%' THEN 1
                    ELSE 0
                END ASC
            ")
            ->orderByRaw('ISNULL(source_due_at) ASC')
            ->orderBy('source_due_at', $this->sortDir);

        return $query;
    }

    public function render()
    {
        $demands     = $this->baseQuery()->paginate($this->perPage);
        $groupedDemands = null;

        if ($this->groupByCase) {
            $groupedDemands = $demands->getCollection()
                ->groupBy(fn (LegalDemand $demand) => (string) ($demand->source_case_number ?: $demand->source_process_number ?: 'sem_caso'))
                ->map(function ($items, $caseNumber) {
                    $items = $items
                        ->sortBy(function (LegalDemand $demand) {
                            $processStatus = mb_strtolower((string) ($demand->process_status_at_import ?? ''));
                            $isClosedByProcessStatus = $processStatus !== '' && str_contains($processStatus, 'encerrad');

                            return [
                                $isClosedByProcessStatus ? 1 : 0,
                                $demand->source_due_at === null ? 1 : 0,
                                optional($demand->source_due_at)->timestamp ?? PHP_INT_MAX,
                            ];
                        })
                        ->values();
                    $first = $items->first();

                    $nearestOpenDeadline = $items
                        ->filter(function (LegalDemand $demand) {
                            $processStatus = mb_strtolower((string) ($demand->process_status_at_import ?? ''));
                            $isOpenByProcessStatus = $processStatus !== '' && !str_contains($processStatus, 'encerrad');

                            return $demand->source_due_at !== null
                                && $isOpenByProcessStatus;
                        })
                        ->sortBy('source_due_at')
                        ->first()?->source_due_at;

                    return [
                        'group_key' => $caseNumber,
                        'number_case' => $first?->source_case_number ?: 'Sem número de caso',
                        'process_number' => $first?->source_process_number_masked ?: ($first?->source_process_number ?: 'Não informado'),
                        'empresa' => $first?->legalCase?->company_name ?: 'Não informada',
                        'firma' => $first?->legalCase?->law_firm ?: 'Não informada',
                        'nearest_open_deadline' => $nearestOpenDeadline,
                        'nearest_open_deadline_ts' => optional($nearestOpenDeadline)->timestamp,
                        'demands' => $items,
                    ];
                })
                ->sortBy([
                    ['nearest_open_deadline_ts', 'asc'],
                    ['number_case', 'asc'],
                ])
                ->values();
        }

        $controllers = User::whereIn(
            'id',
            LegalDemand::externallyActive()
                
                ->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->pluck('controller_user_id')
                ->filter()
                ->unique()
        )->orderBy('name')->get();

        $monitorSicodeClosedButSourceOpen = LegalDemand::query()
            
            ->whereIn('internal_status', ['closed_internal', 'closed_external'])
            ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%'])
            ->count();

        $monitorSourceClosedButSicodeOpen = LegalDemand::query()
            
            ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) LIKE ?", ['%encerrad%'])
            ->whereNotIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])
            ->count();

        return view('livewire.legal.controller.demand-queue', [
            'demands'     => $demands,
            'groupedDemands' => $groupedDemands,
            'controllers' => $controllers,
            'monitorSicodeClosedButSourceOpen' => $monitorSicodeClosedButSourceOpen,
            'monitorSourceClosedButSicodeOpen' => $monitorSourceClosedButSicodeOpen,
        ]);
    }
}
