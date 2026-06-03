<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalDemand;
use App\Models\User;
use App\Services\Legal\LegalDemandBulkService;
use App\Support\Legal\LegalPartyDocument;
use Livewire\{Component, WithPagination};

class DemandQueue extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filtros
    public string $search = '';

    public string $bulkCaseSearchInput = '';

    public array $bulkCaseSearchTerms = [];

    public string $tab = 'all';

    public string $sourceType = '';

    public string $statusFilter = '';

    public string $dueDateFilter = '';

    public string $controllerFilter = '';

    public string $sortBy = 'source_due_at';

    public string $sortDir = 'asc';

    public int    $perPage = 25;
    public bool   $groupByCase = false;
    public array $expandedSubdemands = [];

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

    public bool   $showBulkCaseSearchModal = false;

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

    public function clearFilters(): void
    {
        $this->sourceType = '';
        $this->dueDateFilter = '';
        $this->controllerFilter = '';
        $this->statusFilter = '';
        $this->search = '';
        $this->clearBulkCaseSearch();
    }

    public function applyBulkCaseSearch(): void
    {
        $terms = collect(preg_split('/[\s,;]+/', $this->bulkCaseSearchInput) ?: [])
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->unique()
            ->take(200)
            ->values()
            ->all();

        $this->bulkCaseSearchTerms = $terms;
        $this->showBulkCaseSearchModal = false;
        $this->resetPage();

        $this->dispatchBrowserEvent('swal', [
            'icon' => empty($terms) ? 'warning' : 'success',
            'title' => empty($terms) ? 'Nenhum caso informado' : 'Busca em massa aplicada',
            'html' => empty($terms) ? 'Informe ao menos um número de caso ou processo.' : count($terms) . ' termo(s) carregado(s).',
            'timer' => empty($terms) ? null : 1800,
        ]);
    }

    public function clearBulkCaseSearch(): void
    {
        $this->bulkCaseSearchInput = '';
        $this->bulkCaseSearchTerms = [];
        $this->resetPage();
    }

    public function toggleSubdemands(int $demandId): void
    {
        if (in_array($demandId, $this->expandedSubdemands, true)) {
            $this->expandedSubdemands = array_values(array_filter(
                $this->expandedSubdemands,
                fn (int $id) => $id !== $demandId
            ));
            return;
        }

        $this->expandedSubdemands[] = $demandId;
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

    private function baseQuery(?string $tab = null)
    {
        $tab ??= $this->tab;

        $query = LegalDemand::query()
            ->with([
                'legalCase.adverseParties',
                'legalCase.notes.Orders',
                'controller',
                'currentAssignee',
                'noteInstructions.note.Orders',
                'subdemands.assignedTo',
            ])
            ->withCount('subdemands');

        // Tab filters — "all" intentionally includes every demand; active tabs exclude externally-closed demands.
        $triageScope = function ($q) {
            $q->whereIn('internal_status', ['new_imported', 'triage', 'waiting_controller_action'])
                ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%'])
                ->whereNull('current_assigned_user_id')
                ->whereNull('current_assigned_team_id')
                ->whereDoesntHave('assignments', function ($aq) {
                    $aq->whereIn('status', ['sent', 'received', 'returned_for_correction']);
                });
        };

        $inProgressStatuses = [
            'sent_to_field',
            'field_received',
            'waiting_field_response',
            'returned_by_field',
            'returned_for_correction',
            'under_controller_review',
            'ready_to_close_external',
            'reopened',
        ];

        match ($tab) {
            'all' => null,
            'triage' => $query->externallyActive()->where($triageScope),
            'in_progress' => $query->externallyActive()->whereIn('internal_status', $inProgressStatuses),
            'overdue' => $query->externallyActive()
                ->overdue()
                ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%']),
            'closed' => $query->where(function ($q) {
                $q->whereIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])
                    ->orWhere(fn ($sub) => $sub->externallyClosed());
            }),
            default => $query->externallyActive()
                ->where(function ($q) use ($triageScope, $inProgressStatuses) {
                    $q->where($triageScope)
                        ->orWhereIn('internal_status', $inProgressStatuses);
                }),
        };

        if ($this->search) {
            $s = "%{$this->search}%";
            $documentHash = $this->searchDocumentHash($this->search);
            $query->where(
                function ($q) use ($s, $documentHash) {
                    $q->where('source_case_number', 'like', $s)
                        ->orWhere('source_process_number', 'like', $s)
                        ->orWhere('title', 'like', $s)
                        ->orWhere('source_subject', 'like', $s)
                        ->orWhereHas('legalCase.adverseParties', fn ($partyQuery) => $partyQuery->where('name', 'like', $s));

                    if ($documentHash) {
                        $q->orWhereHas(
                            'legalCase.adverseParties',
                            fn ($partyQuery) => $partyQuery->where('document_hash', $documentHash)
                        );
                    }
                }
            );
        }

        if (!empty($this->bulkCaseSearchTerms)) {
            $terms = array_slice($this->bulkCaseSearchTerms, 0, 200);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%' . $term . '%';
                    $digits = preg_replace('/\D+/', '', (string) $term);

                    $q->orWhere('source_case_number', 'like', $like)
                        ->orWhere('source_process_number', 'like', $like);

                    if ($digits !== '' && $digits !== $term) {
                        $q->orWhere('source_case_number', 'like', '%' . $digits . '%')
                            ->orWhere('source_process_number', 'like', '%' . $digits . '%');
                    }
                }
            });
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
                    WHEN internal_status IN ('closed_internal', 'closed_external', 'cancelled', 'ignored')
                        OR LOWER(COALESCE(process_status_at_import, '')) LIKE '%encerrad%' THEN 2
                    WHEN source_due_at IS NULL THEN 1
                    ELSE 0
                END ASC
            ")
            ->orderBy('source_due_at', 'asc');

        return $query;
    }

    private function searchDocumentHash(string $search): ?string
    {
        $digits = LegalPartyDocument::digits($search);

        if (!in_array(strlen($digits), [11, 14], true) || !LegalPartyDocument::validate($digits)) {
            return null;
        }

        return LegalPartyDocument::hash($digits);
    }

    private function kpis(): array
    {
        return [
            'total_active' => (clone $this->baseQuery('all'))->count(),
            'overdue' => (clone $this->baseQuery('overdue'))->count(),
            'awaiting_field' => (clone $this->baseQuery('in_progress'))->count(),
            'triage' => (clone $this->baseQuery('triage'))->count(),
            'closed' => (clone $this->baseQuery('closed'))->count(),
        ];
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
                            $isOpenDemand = $this->isOpenDemandForDeadline($demand);

                            return [
                                $isOpenDemand ? 0 : 2,
                                $demand->source_due_at === null ? 1 : 0,
                                optional($demand->source_due_at)->timestamp ?? PHP_INT_MAX,
                            ];
                        })
                        ->values();
                    $first = $items->first();

                    $nearestOpenDeadline = $items
                        ->filter(fn (LegalDemand $demand) => $demand->source_due_at !== null && $this->isOpenDemandForDeadline($demand))
                        ->sortBy('source_due_at')
                        ->first()?->source_due_at;

                    return [
                        'group_key' => $caseNumber,
                        'number_case' => $first?->source_case_number ?: 'Sem número de caso',
                        'process_number' => $first?->source_process_number_masked ?: ($first?->source_process_number ?: 'Não informado'),
                        'empresa' => $first?->legalCase?->company_name ?: 'Não informada',
                        'firma' => $first?->legalCase?->law_firm ?: 'Não informada',
                        'legal_case' => $first?->legalCase,
                        'adverse_parties' => $first?->legalCase,
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
            'kpis' => $this->kpis(),
            'monitorSicodeClosedButSourceOpen' => $monitorSicodeClosedButSourceOpen,
            'monitorSourceClosedButSicodeOpen' => $monitorSourceClosedButSicodeOpen,
        ]);
    }

    private function isOpenDemandForDeadline(LegalDemand $demand): bool
    {
        $internalStatus = $demand->internal_status instanceof \BackedEnum
            ? $demand->internal_status->value
            : (string) $demand->internal_status;

        if (in_array($internalStatus, ['closed_internal', 'closed_external', 'cancelled', 'ignored'], true)) {
            return false;
        }

        $processStatus = mb_strtolower((string) ($demand->process_status_at_import ?? ''));

        return $processStatus === '' || !str_contains($processStatus, 'encerrad');
    }
}
