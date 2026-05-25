<?php

namespace App\Http\Livewire\ProjectReview;

use App\Jobs\Reports\ExportProjectReviewQueueListJob;
use App\Models\Company;
use App\Models\File;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\ProjectReviewCategory;
use App\Models\ProjectReviewCycle;
use App\Models\ProjectReviewItem;
use App\Models\ProjectReviewMessage;
use App\Models\ProjectReviewDraft;
use App\Models\ProjectReviewSubcategory;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Queue extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $mass_search = '';
    public string $company_id = '';
    public string $cost_share_filter = '';
    public string $cost_metric = '';
    public string $cost_operator = '>';
    public ?string $cost_value = null;
    public string $note_type_filter = '';
    public string $tab = 'pending';
    public string $mode = 'pending';
    public int $perPage = 30;
    public bool $selectPage = false;
    public array $selectedProductionIds = [];

    public ?Production $selectedProduction = null;
    public ?Production $drawingProduction = null;
    public ?ProjectReviewCycle $selectedCycle = null;

    public string $analystNote = '';
    public string $requiresSapRelease = '';
    public array $findingRows = [];
    public string $newReply = '';
    public ?int $selectedCategoryId = null;
    public ?int $selectedSubcategoryId = null;
    public string $selectedPointLabel = 'P1';
    public string $selectedPointFilter = '';
    public array $pointRenameInputs = [];
    public string $selectedOrigin = 'PROJETO';
    public string $selectedActionType = 'FALTA';
    public string $duplicateMode = '';
    public string $duplicateReference = '';
    public string $duplicatePointLabel = '';
    public array $collapsedGroups = [];
    public array $collapsedCategories = [];
    public array $collapsedSubcategories = [];
    public array $taxonomySubcategories = [];
    public array $taxonomyCategories = [];
    public array $draftProductionIds = [];
    public ?string $draftSavedAt = null;

    protected $listeners = [
        'refresh_list' => '$refresh',
        'savedFiles' => 'onFilesSaved',
        'openReviewFromNotification' => 'openReviewFromNotification',
    ];

    public function mount(string $mode = 'pending'): void
    {
        $this->mode = $mode;
        $this->tab = $mode === 'history' ? 'history' : 'pending';
        $this->loadTaxonomy();
    }

    public function updatingSearch(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingMassSearch(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingCompanyId(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingCostShareFilter(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingCostMetric(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingCostOperator(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingCostValue(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingNoteTypeFilter(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatedSelectPage(bool $value): void
    {
        if (!$value) {
            $this->selectedProductionIds = [];
            return;
        }

        $this->selectedProductionIds = $this->lists
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
    }

    public function updatedSelectedProductionIds(): void
    {
        $pageIds = $this->lists->pluck('id')->map(fn($id) => (string) $id)->values();
        $selected = collect($this->selectedProductionIds)->map(fn($id) => (string) $id);
        $this->selectPage = $pageIds->isNotEmpty() && $pageIds->every(fn($id) => $selected->contains($id));
    }

    public function getListsProperty()
    {
        $query = $this->baseListQuery();

        $lists = $query
            ->orderByRaw("
                CASE
                    WHEN (
                        SELECT notes.type_note
                        FROM notes
                        WHERE notes.id = productions.note_id
                        LIMIT 1
                    ) = 2 THEN 0
                    ELSE 1
                END ASC
            ")
            ->orderByRaw("
                COALESCE((
                    SELECT notes.days_left
                    FROM notes
                    WHERE notes.id = productions.note_id
                    LIMIT 1
                ), 2147483647) ASC
            ")
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($this->resolvePerPage());

        $this->syncDraftFlagsForPage($lists);

        return $lists;
    }

    private function resolvePerPage(): int
    {
        $allowed = [30, 50, 100, 200];
        return in_array($this->perPage, $allowed, true) ? $this->perPage : 30;
    }

    public function exportList(): void
    {
        ExportProjectReviewQueueListJob::dispatch(
            $this->exportFilters(),
            (string) auth()->id()
        );

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'>
                <p>Sua lista está sendo gerada.</p>
                <p class='mb-0'><strong>Você será notificado quando o download estiver pronto.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    private function baseListQuery()
    {
        $query = Production::query()
            ->with([
                'Note',
                'User',
                'Company',
                'Service',
                'ProjectReviewCycles' => function ($q) {
                    $q->with('Orders')->latest('round_number');
                },
            ])
            ->withCount([
                'ProjectReviewCycles as rejected_cycles_count' => function ($q) {
                    $q->where('decision', 'REJECTED');
                },
                'Notetimelines as rejected_status_timeline_count' => function ($q) {
                    $q->where('status', Production::STATUS_REJECTED_PROJECT_REVIEW);
                },
            ])
            ->withMax('ProjectReviewCycles as latest_round_number', 'round_number');

        if ($this->tab === 'pending') {
            $query->where('status', Production::STATUS_IN_PROJECT_REVIEW);
        } else {
            $query->whereIn('status', [5, Production::STATUS_REJECTED_PROJECT_REVIEW, Production::STATUS_RELEASED_TO_FINISH])
                ->whereHas('ProjectReviewCycles', function ($q) {
                    $q->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED']);
                });
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->whereHas('Note', function ($q) use ($term) {
                $q->where('note', 'like', $term)
                    ->orWhere('numPedido', 'like', $term)
                    ->orWhere('material', 'like', $term);
            });
        }

        $massTokens = collect(preg_split('/[\s,;\n\r\t]+/', trim($this->mass_search)) ?: [])
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values();
        if ($massTokens->isNotEmpty()) {
            $query->where(function ($outer) use ($massTokens) {
                $outer->whereHas('Note', function ($noteQuery) use ($massTokens) {
                    $noteQuery->where(function ($noteWhere) use ($massTokens) {
                        foreach ($massTokens as $token) {
                            $noteWhere->orWhere('note', 'like', '%' . $token . '%')
                                ->orWhere('numPedido', 'like', '%' . $token . '%');
                        }
                    });
                })->orWhereHas('ProjectReviewCycles.Orders', function ($orderQuery) use ($massTokens) {
                    $orderQuery->where(function ($orderWhere) use ($massTokens) {
                        foreach ($massTokens as $token) {
                            $orderWhere->orWhere('order_number', 'like', '%' . $token . '%');
                        }
                    });
                });
            });
        }

        if ($this->company_id !== '') {
            $query->where('company_id', $this->company_id);
        }

        if ($this->note_type_filter === 'retorno') {
            $query->whereHas('Note', fn ($q) => $q->where('type_note', 2));
        } elseif ($this->note_type_filter === 'inicial') {
            $query->whereHas('Note', fn ($q) => $q->where('type_note', '!=', 2));
        }

        $costFilter = $this->cost_share_filter;
        if (in_array($costFilter, ['client_51', 'company_51', 'both_51'], true)) {
            $this->applyLatestCycleOrdersFilter($query, function ($orderQuery) use ($costFilter) {
                $ratioExprClient = '(project_review_orders.client_cost / NULLIF(project_review_orders.total_cost, 0))';
                $ratioExprCompany = '(project_review_orders.company_cost / NULLIF(project_review_orders.total_cost, 0))';

                $orderQuery->where('project_review_orders.total_cost', '>', 0);

                if ($costFilter === 'client_51') {
                    $orderQuery->whereRaw("{$ratioExprClient} >= 0.51");
                    return;
                }

                if ($costFilter === 'company_51') {
                    $orderQuery->whereRaw("{$ratioExprCompany} >= 0.51");
                    return;
                }

                $orderQuery->where(function ($q) use ($ratioExprClient, $ratioExprCompany) {
                    $q->whereRaw("{$ratioExprClient} >= 0.51")
                        ->orWhereRaw("{$ratioExprCompany} >= 0.51");
                });
            });
        }

        if (in_array($this->cost_metric, ['total_cost', 'company_cost', 'client_cost'], true)
            && in_array($this->cost_operator, ['>', '<'], true)
            && is_numeric($this->cost_value)
        ) {
            $metric = $this->cost_metric;
            $operator = $this->cost_operator;
            $value = (float) $this->cost_value;

            $this->applyLatestCycleOrdersFilter($query, function ($orderQuery) use ($metric, $operator, $value) {
                $orderQuery->where($metric, $operator, $value);
            });
        }

        return $query;
    }

    private function applyLatestCycleOrdersFilter($query, \Closure $orderFilter): void
    {
        $query->whereHas('ProjectReviewCycles', function ($cycleQuery) use ($orderFilter) {
            $cycleQuery
                ->whereRaw('project_review_cycles.id = (
                    SELECT prc2.id
                    FROM project_review_cycles prc2
                    WHERE prc2.production_id = project_review_cycles.production_id
                    ORDER BY prc2.round_number DESC, prc2.id DESC
                    LIMIT 1
                )')
                ->whereHas('Orders', $orderFilter);
        });
    }

    private function exportFilters(): array
    {
        return [
            'search' => $this->search,
            'mass_search' => $this->mass_search,
            'company_id' => $this->company_id,
            'cost_share_filter' => $this->cost_share_filter,
            'cost_metric' => $this->cost_metric,
            'cost_operator' => $this->cost_operator,
            'cost_value' => $this->cost_value,
            'note_type_filter' => $this->note_type_filter,
            'tab' => $this->tab,
        ];
    }

    public function getCompaniesProperty()
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getSubcategoriesProperty()
    {
        return collect($this->taxonomySubcategories);
    }

    public function getCategoriesProperty()
    {
        return collect($this->taxonomyCategories);
    }

    public function getAvailablePointLabelsProperty()
    {
        return collect($this->findingRows)
            ->map(fn ($row) => $this->normalizePointLabel($row['point_label'] ?? ''))
            ->filter(fn ($label) => $label !== '')
            ->unique()
            ->sort()
            ->values();
    }

    public function updatedSelectedPointFilter(): void
    {
        $this->selectedPointFilter = $this->normalizePointFilter($this->selectedPointFilter);
    }

    public function updatedSelectedPointLabel(): void
    {
        $this->selectedPointLabel = $this->normalizePointLabel($this->selectedPointLabel);
    }

    public function getAvailableSubcategoriesProperty()
    {
        if (!$this->selectedCategoryId) {
            return collect();
        }

        return $this->subcategories
            ->where('category_id', (int) $this->selectedCategoryId)
            ->sortBy('name')
            ->values();
    }

    public function getAvailableItemsProperty()
    {
        if (!$this->selectedSubcategoryId) {
            return collect();
        }

        $subcategory = $this->subcategories->firstWhere('id', (int) $this->selectedSubcategoryId);
        if (!$subcategory) {
            return collect();
        }

        $items = data_get($subcategory, 'Items', data_get($subcategory, 'items', []));

        return collect($items)
            ->filter(fn ($item) => (bool) data_get($item, 'active', false))
            ->sortBy(fn ($item) => (string) data_get($item, 'name', ''))
            ->values();
    }

    public function getFindingsTreeProperty()
    {
        $subcategories = $this->subcategories->keyBy('id');
        $originSort = ['LEVANTAMENTO' => 1, 'PROJETO' => 2, 'AMBOS' => 3];

        $flat = collect($this->findingRows)
            ->filter(function ($row) {
                if ($this->selectedPointFilter === '') {
                    return true;
                }

                return $this->normalizePointLabel($row['point_label'] ?? '') === $this->selectedPointFilter;
            })
            ->map(function ($row, $index) use ($subcategories) {
                $subcategory = $subcategories->get((int) ($row['subcategory_id'] ?? 0));
                $items = data_get($subcategory, 'Items', data_get($subcategory, 'items', []));
                $selectedItem = collect($items)
                    ->firstWhere('id', (int) ($row['item_id'] ?? 0));
                $categoryName = data_get($subcategory, 'Category.name', data_get($subcategory, 'category.name', 'Sem categoria'));
                $origin = (string) ($row['origin'] ?? 'PROJETO');
                if (!in_array($origin, ['LEVANTAMENTO', 'PROJETO', 'AMBOS'], true)) {
                    $origin = 'PROJETO';
                }
                $pointLabel = $this->normalizePointLabel($row['point_label'] ?? '');

                return [
                    'index' => $index,
                    'point_label' => $pointLabel,
                    'point_key' => 'point_' . md5($pointLabel),
                    'subcategory_id' => (int) ($row['subcategory_id'] ?? 0),
                    'subcategory_name' => data_get($subcategory, 'name', 'Subcategoria não encontrada'),
                    'category_name' => $categoryName,
                    'item_id' => $row['item_id'] ?? null,
                    'item_name' => $row['item_name'] ?? data_get($selectedItem, 'name'),
                    'origin' => $origin,
                    'action_type' => $row['action_type'] ?? null,
                    'quantity' => $row['quantity'] ?? null,
                    'note' => $row['note'] ?? null,
                    'category_key' => 'cat_' . md5((string) ($categoryName ?: 'sem-categoria')),
                    'subcategory_key' => 'sub_' . md5($pointLabel . '|' . (int) ($row['subcategory_id'] ?? 0)),
                ];
            })
            ->values();

        $grouped = $flat
            ->groupBy('point_label')
            ->map(function ($pointRows, $pointLabel) use ($originSort) {
                return [
                    'point_label' => $pointLabel,
                    'point_key' => 'point_' . md5((string) $pointLabel),
                    'categories' => $pointRows
                        ->groupBy('category_name')
                        ->map(function ($categoryRows, $categoryName) use ($originSort, $pointLabel) {
                            return [
                                'category_name' => $categoryName,
                                'category_key' => 'cat_' . md5((string) $pointLabel . '|' . (string) $categoryName),
                                'subcategories' => $categoryRows
                                    ->groupBy('subcategory_key')
                                    ->map(function ($subRows) use ($originSort) {
                                        $first = $subRows->first();
                                        return [
                                            'subcategory_name' => $first['subcategory_name'],
                                            'subcategory_key' => $first['subcategory_key'],
                                            'origins' => collect($subRows)
                                                ->groupBy('origin')
                                                ->sortBy(fn ($rows, $origin) => $originSort[$origin] ?? 99)
                                                ->map(function ($rows, $origin) {
                                                    return [
                                                        'origin' => $origin,
                                                        'rows' => $rows->values()->all(),
                                                    ];
                                                })
                                                ->values()
                                                ->all(),
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy(fn ($group) => $group['point_label'] ?? '')
            ->values()
            ->all();

        $activeRenameKeys = [];
        foreach ($grouped as $group) {
            $label = (string) ($group['point_label'] ?? '');
            $key = 'rename_' . md5($label);
            $activeRenameKeys[] = $key;
            if (!array_key_exists($key, $this->pointRenameInputs) || trim((string) $this->pointRenameInputs[$key]) === '') {
                $this->pointRenameInputs[$key] = $label;
            }
        }
        $this->pointRenameInputs = collect($this->pointRenameInputs)
            ->only($activeRenameKeys)
            ->all();

        return $grouped;
    }

    public function updatedSelectedCategoryId(): void
    {
        $this->selectedSubcategoryId = null;
    }

    public function openReview(int $productionId): void
    {
        $this->resetReviewForm();
        Log::info('project_review.openReview.start', ['production_id' => $productionId, 'user_id' => auth()->id()]);

        try {
            $this->selectedProduction = Production::with([
                'Note',
                'User',
                'Company',
                'Service',
                'Analise',
                'ProjectReviewMessages.User',
                'ProjectReviewCycles' => function ($q) {
                    $q->with([
                        'Orders',
                        'DecidedBy',
                        'Messages' => function ($mq) {
                            $mq->with('User')
                                ->orderBy('created_at')
                                ->orderBy('id');
                        },
                    ])->latest('round_number');
                },
            ])->findOrFail($productionId);

            $this->selectedCycle = ProjectReviewCycle::query()
                ->with([
                    'Orders',
                    'Findings.Subcategory.Category',
                    'Findings.Item',
                    'DecidedBy',
                    'Messages' => function ($mq) {
                        $mq->with('User')
                            ->orderBy('created_at')
                            ->orderBy('id');
                    },
                ])
                ->where('production_id', $this->selectedProduction->id)
                ->orderByRaw("CASE WHEN decision = 'PENDING' THEN 0 ELSE 1 END")
                ->orderByDesc('round_number')
                ->orderByDesc('id')
                ->first();

            if (!$this->selectedCycle) {
                Log::warning('project_review.openReview.no_cycle', ['production_id' => $productionId]);
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Rodada não encontrada',
                    'html' => 'Não encontramos rodadas de Análise de Projeto para esta produção.',
                    'timer' => 3500,
                ]);
                return;
            }

            if ($this->selectedCycle) {
                $this->findingRows = $this->selectedCycle->Findings->map(function ($f) {
                    return [
                        'point_label' => $this->normalizePointLabel($f->point_label ?? ''),
                        'subcategory_id' => (int) $f->subcategory_id,
                        'item_id' => $f->item_id ? (int) $f->item_id : null,
                        'item_name' => optional($f->Item)->name,
                        'origin' => (string) ($f->origin ?: 'PROJETO'),
                        'action_type' => $f->action_type,
                        'quantity' => $f->quantity,
                        'note' => $f->note,
                        'is_conform' => false,
                    ];
                })->values()->all();

                if (
                    $this->selectedCycle->decision === 'PENDING'
                    && empty($this->findingRows)
                ) {
                    $previousRejectedCycle = $this->selectedProduction->ProjectReviewCycles
                        ->where('round_number', '<', $this->selectedCycle->round_number)
                        ->where('decision', 'REJECTED')
                        ->sortByDesc('round_number')
                        ->first();

                    if ($previousRejectedCycle) {
                        $previousRejectedCycle = ProjectReviewCycle::query()
                            ->with([
                                'Findings.Subcategory.Category',
                                'Findings.Item',
                            ])
                            ->find($previousRejectedCycle->id);
                    }

                    if ($previousRejectedCycle) {
                        $this->findingRows = $previousRejectedCycle->Findings->map(function ($f) {
                            return [
                                'point_label' => $this->normalizePointLabel($f->point_label ?? ''),
                                'subcategory_id' => (int) $f->subcategory_id,
                                'item_id' => $f->item_id ? (int) $f->item_id : null,
                                'item_name' => optional($f->Item)->name,
                                'origin' => (string) ($f->origin ?: 'PROJETO'),
                                'action_type' => $f->action_type,
                                'quantity' => $f->quantity,
                                'note' => $f->note,
                                'is_conform' => false,
                            ];
                        })->values()->all();
                    }
                }
            }

            $this->restoreDraft();

            if ($this->selectedPointLabel === '') {
                $this->selectedPointLabel = $this->normalizePointLabel($this->availablePointLabels->first() ?? '');
            }

            $this->refreshSelectedProductionMessages();

            Log::info('project_review.openReview.success', [
                'production_id' => $this->selectedProduction->id ?? null,
                'cycle_id' => $this->selectedCycle->id ?? null,
                'round' => $this->selectedCycle->round_number ?? null,
            ]);
            $this->dispatchBrowserEvent('showModal', ['id' => 'projectReviewModal']);
        } catch (\Throwable $e) {
            report($e);
            Log::error('project_review.openReview.error', [
                'production_id' => $productionId,
                'message' => $e->getMessage(),
            ]);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Não foi possível abrir a análise',
                'html' => 'A solicitação falhou ao carregar os dados da produção. Atualize a tela e tente novamente.',
                'timer' => 4200,
            ]);
        }
    }

    public function openReviewFromNotification($productionId): void
    {
        $id = (int) $productionId;
        if ($id <= 0) {
            return;
        }

        $this->openReview($id);
    }

    public function saveDraftManually(): void
    {
        $saved = $this->persistDraft();
        if (!$saved) {
            return;
        }

        $this->dispatchBrowserEvent('hideModal');
        $this->resetReviewForm();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Rascunho salvo',
            'timer' => 1600,
        ]);
    }

    public function saveDraftSilently(): void
    {
        $this->persistDraft();
    }

    public function saveAnalystFiles(): void
    {
        if (!$this->drawingProduction) {
            return;
        }

        $this->emitTo('files.manager.create-prod-files', 'saveFiles');
    }

    public function onFilesSaved(): void
    {
        if (!$this->drawingProduction) {
            return;
        }

        $this->drawingProduction->load('Files', 'Service', 'Note.Files.Service');

        if ($this->selectedProduction && $this->selectedProduction->id === $this->drawingProduction->id) {
            $this->selectedProduction->setRelation('Files', $this->drawingProduction->Files);
        }

        if ($this->selectedProduction?->Note) {
            $this->selectedProduction->Note->setRelation('Files', $this->drawingProduction->Note->Files);
        }
    }

    public function addEmptySubcategory(): void
    {
        if (!$this->selectedSubcategoryId) {
            return;
        }

        $pointLabel = $this->normalizePointLabel($this->selectedPointLabel);

        $this->findingRows[] = [
            'point_label' => $pointLabel,
            'subcategory_id' => (int) $this->selectedSubcategoryId,
            'item_id' => null,
            'item_name' => null,
            'origin' => $this->selectedOrigin,
            'action_type' => null,
            'quantity' => null,
            'note' => '',
            'is_conform' => false,
        ];
    }

    public function addItemToFindings(int $itemId): void
    {
        if (!$this->selectedSubcategoryId) {
            return;
        }

        $pointLabel = $this->normalizePointLabel($this->selectedPointLabel);

        $item = $this->availableItems->firstWhere('id', $itemId);
        if (!$item) {
            return;
        }

        $alreadyExists = collect($this->findingRows)->contains(function ($row) use ($itemId, $pointLabel) {
            return (int) ($row['subcategory_id'] ?? 0) === (int) $this->selectedSubcategoryId
                && (int) ($row['item_id'] ?? 0) === $itemId
                && (string) ($row['origin'] ?? 'PROJETO') === $this->selectedOrigin
                && (string) ($row['action_type'] ?? '') === $this->selectedActionType
                && $this->normalizePointLabel($row['point_label'] ?? '') === $pointLabel;
        });

        if ($alreadyExists) {
            return;
        }

        $this->findingRows[] = [
            'point_label' => $pointLabel,
            'subcategory_id' => (int) $this->selectedSubcategoryId,
            'item_id' => $itemId,
            'item_name' => data_get($item, 'name'),
            'origin' => $this->selectedOrigin,
            'action_type' => $this->selectedActionType,
            'quantity' => 1,
            'note' => '',
            'is_conform' => false,
        ];
    }

    public function toggleCategoryGroup(string $categoryKey): void
    {
        $this->collapsedCategories[$categoryKey] = !($this->collapsedCategories[$categoryKey] ?? false);
    }

    public function toggleSubcategoryGroup(string $subcategoryKey): void
    {
        $this->collapsedSubcategories[$subcategoryKey] = !($this->collapsedSubcategories[$subcategoryKey] ?? false);
    }

    public function toggleGroup(string $groupKey): void
    {
        $this->collapsedGroups[$groupKey] = !($this->collapsedGroups[$groupKey] ?? false);
    }

    public function removeFindingRow(int $index): void
    {
        if (isset($this->findingRows[$index])) {
            unset($this->findingRows[$index]);
            $this->findingRows = array_values($this->findingRows);
        }
    }

    public function requestDuplicateFindingRow(int $index): void
    {
        if (!isset($this->findingRows[$index])) {
            return;
        }

        $this->duplicateMode = 'row';
        $this->duplicateReference = (string) $index;
        $this->duplicatePointLabel = $this->normalizePointLabel($this->findingRows[$index]['point_label'] ?? '');
    }

    public function requestDuplicateSubcategoryGroup(string $subcategoryKey): void
    {
        $firstRow = collect($this->findingRows)->first(function ($row) use ($subcategoryKey) {
            $pointLabel = $this->normalizePointLabel($row['point_label'] ?? '');
            return 'sub_' . md5($pointLabel . '|' . (int) ($row['subcategory_id'] ?? 0)) === $subcategoryKey;
        });

        if (!$firstRow) {
            return;
        }

        $this->duplicateMode = 'subcategory';
        $this->duplicateReference = $subcategoryKey;
        $this->duplicatePointLabel = $this->normalizePointLabel($firstRow['point_label'] ?? '');
    }

    public function requestDuplicatePointGroup(string $pointLabel): void
    {
        $normalizedPointLabel = $this->normalizePointLabel($pointLabel);
        $hasRows = collect($this->findingRows)->contains(function ($row) use ($normalizedPointLabel) {
            return $this->normalizePointLabel($row['point_label'] ?? '') === $normalizedPointLabel;
        });

        if (!$hasRows) {
            return;
        }

        $this->duplicateMode = 'point';
        $this->duplicateReference = $normalizedPointLabel;
        $this->duplicatePointLabel = $normalizedPointLabel;
    }

    public function renamePointGroup(string $sourcePointLabel, string $inputKey): void
    {
        $sourceNormalized = $this->normalizePointLabel($sourcePointLabel);
        $targetRaw = (string) ($this->pointRenameInputs[$inputKey] ?? '');
        $targetNormalized = $this->normalizePointLabel($targetRaw);

        if ($targetNormalized === '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Informe a ref:',
                'timer' => 2200,
            ]);
            return;
        }

        $this->findingRows = collect($this->findingRows)
            ->map(function ($row) use ($sourceNormalized, $targetNormalized) {
                if ($this->normalizePointLabel($row['point_label'] ?? '') === $sourceNormalized) {
                    $row['point_label'] = $targetNormalized;
                }
                return $row;
            })
            ->values()
            ->all();

        $this->selectedPointLabel = $targetNormalized;
        $this->selectedPointFilter = '';
        $this->pointRenameInputs = [];
    }

    public function cancelDuplicate(): void
    {
        $this->duplicateMode = '';
        $this->duplicateReference = '';
        $this->duplicatePointLabel = '';
    }

    public function confirmDuplicate(): void
    {
        $targetPointLabel = $this->normalizePointLabel($this->duplicatePointLabel);
        if ($targetPointLabel === '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Informe o nome da ref:',
                'timer' => 2200,
            ]);
            return;
        }

        if ($this->duplicateMode === 'row') {
            if (!is_numeric($this->duplicateReference)) {
                return;
            }
            $this->duplicateFindingRow((int) $this->duplicateReference, $targetPointLabel);
        } elseif ($this->duplicateMode === 'subcategory') {
            if ($this->duplicateReference === '') {
                return;
            }
            $this->duplicateSubcategoryGroup($this->duplicateReference, $targetPointLabel);
        } elseif ($this->duplicateMode === 'point') {
            if ($this->duplicateReference === '') {
                return;
            }
            $this->duplicatePointGroup($this->duplicateReference, $targetPointLabel);
        } else {
            return;
        }

        $this->selectedPointLabel = $targetPointLabel;
        $this->selectedPointFilter = '';
        $this->cancelDuplicate();
    }

    public function duplicateFindingRow(int $index, ?string $targetPointLabel = null): void
    {
        if (!isset($this->findingRows[$index])) {
            return;
        }

        $sourceRow = $this->findingRows[$index];
        $cloneRow = [
            'point_label' => $this->normalizePointLabel($targetPointLabel ?? ($sourceRow['point_label'] ?? '')),
            'subcategory_id' => (int) ($sourceRow['subcategory_id'] ?? 0),
            'item_id' => empty($sourceRow['item_id']) ? null : (int) $sourceRow['item_id'],
            'item_name' => $sourceRow['item_name'] ?? null,
            'origin' => (string) ($sourceRow['origin'] ?? 'PROJETO'),
            'action_type' => $sourceRow['action_type'] ?? null,
            'quantity' => empty($sourceRow['quantity']) ? null : (int) $sourceRow['quantity'],
            'note' => (string) ($sourceRow['note'] ?? ''),
            'is_conform' => false,
        ];

        array_splice($this->findingRows, $index + 1, 0, [$cloneRow]);
        $this->findingRows = array_values($this->findingRows);
    }

    public function removeSubcategoryGroup(string $subcategoryKey): void
    {
        $this->findingRows = collect($this->findingRows)
            ->reject(function ($row) use ($subcategoryKey) {
                $pointLabel = $this->normalizePointLabel($row['point_label'] ?? '');
                return 'sub_' . md5($pointLabel . '|' . (int) ($row['subcategory_id'] ?? 0)) === $subcategoryKey;
            })
            ->values()
            ->all();
    }

    public function duplicateSubcategoryGroup(string $subcategoryKey, ?string $targetPointLabel = null): void
    {
        $rowsToDuplicate = collect($this->findingRows)
            ->filter(function ($row) use ($subcategoryKey) {
                $pointLabel = $this->normalizePointLabel($row['point_label'] ?? '');
                return 'sub_' . md5($pointLabel . '|' . (int) ($row['subcategory_id'] ?? 0)) === $subcategoryKey;
            })
            ->map(function ($row) use ($targetPointLabel) {
                return [
                    'point_label' => $this->normalizePointLabel($targetPointLabel ?? ($row['point_label'] ?? '')),
                    'subcategory_id' => (int) ($row['subcategory_id'] ?? 0),
                    'item_id' => empty($row['item_id']) ? null : (int) $row['item_id'],
                    'item_name' => $row['item_name'] ?? null,
                    'origin' => (string) ($row['origin'] ?? 'PROJETO'),
                    'action_type' => $row['action_type'] ?? null,
                    'quantity' => empty($row['quantity']) ? null : (int) $row['quantity'],
                    'note' => (string) ($row['note'] ?? ''),
                    'is_conform' => false,
                ];
            })
            ->values()
            ->all();

        if (empty($rowsToDuplicate)) {
            return;
        }

        $this->findingRows = array_values(array_merge($this->findingRows, $rowsToDuplicate));
    }

    public function duplicatePointGroup(string $sourcePointLabel, ?string $targetPointLabel = null): void
    {
        $sourceNormalized = $this->normalizePointLabel($sourcePointLabel);
        $targetNormalized = $this->normalizePointLabel($targetPointLabel ?? $sourcePointLabel);

        $rowsToDuplicate = collect($this->findingRows)
            ->filter(function ($row) use ($sourceNormalized) {
                return $this->normalizePointLabel($row['point_label'] ?? '') === $sourceNormalized;
            })
            ->map(function ($row) use ($targetNormalized) {
                return [
                    'point_label' => $targetNormalized,
                    'subcategory_id' => (int) ($row['subcategory_id'] ?? 0),
                    'item_id' => empty($row['item_id']) ? null : (int) $row['item_id'],
                    'item_name' => $row['item_name'] ?? null,
                    'origin' => (string) ($row['origin'] ?? 'PROJETO'),
                    'action_type' => $row['action_type'] ?? null,
                    'quantity' => empty($row['quantity']) ? null : (int) $row['quantity'],
                    'note' => (string) ($row['note'] ?? ''),
                    'is_conform' => false,
                ];
            })
            ->values()
            ->all();

        if (empty($rowsToDuplicate)) {
            return;
        }

        $this->findingRows = array_values(array_merge($this->findingRows, $rowsToDuplicate));
    }

    public function removeCategoryGroup(string $categoryKey): void
    {
        $subcategoriesById = $this->subcategories->keyBy('id');

        $this->findingRows = collect($this->findingRows)
            ->reject(function ($row) use ($categoryKey, $subcategoriesById) {
                $subcategory = $subcategoriesById->get((int) ($row['subcategory_id'] ?? 0));
                $rowCategoryName = data_get($subcategory, 'Category.name', data_get($subcategory, 'category.name', 'sem-categoria'));
                $pointLabel = $this->normalizePointLabel($row['point_label'] ?? '');
                $rowCategoryKey = 'cat_' . md5((string) $pointLabel . '|' . (string) $rowCategoryName);
                return $rowCategoryKey === $categoryKey;
            })
            ->values()
            ->all();
    }

    public function approve(): void
    {
        $this->resolveCycle('APPROVED');
    }

    public function approveWithRemarks(): void
    {
        $this->resolveCycle('APPROVED_WITH_REMARKS');
    }

    public function approveSelected(): void
    {
        $ids = collect($this->selectedProductionIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione ao menos uma produção',
                'timer' => 2200,
            ]);
            return;
        }

        $productions = Production::query()
            ->with([
                'Note',
                'User',
                'ProjectReviewCycles' => function ($q) {
                    $q->where('decision', 'PENDING')->latest('round_number');
                },
            ])
            ->whereIn('id', $ids->all())
            ->where('status', Production::STATUS_IN_PROJECT_REVIEW)
            ->get();

        if ($productions->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma produção pendente elegível para aprovação em massa',
                'timer' => 2800,
            ]);
            $this->clearBulkSelection();
            return;
        }

        $approvedCount = 0;

        DB::transaction(function () use ($productions, &$approvedCount) {
            foreach ($productions as $production) {
                $cycle = $production->ProjectReviewCycles->first();
                if (!$cycle) {
                    continue;
                }

                $cycle->update([
                    'decision' => 'APPROVED',
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                    'analyst_note' => null,
                ]);

                $production->update([
                    'status' => 5,
                ]);

                Notetimeline::create([
                    'note_id' => $production->note_id,
                    'service_id' => $production->service_id,
                    'production_id' => $production->id,
                    'user_id' => auth()->id(),
                    'info' => 'Projeto aprovado na Análise de Projeto.',
                    'status' => 5,
                ]);

                if ($production->User) {
                    $production->User->notify(new SystemNotification(
                        titulo: 'Projeto Aprovado na Análise',
                        mensagem: 'A nota <strong>' . ($production->Note->note ?? '-') . '</strong> foi aprovada na análise de projeto.',
                        link: route('services.accompany', ['service' => $production->service_id]),
                        status: 1,
                        extras: []
                    ));
                }

                $approvedCount++;
            }
        });

        $this->clearBulkSelection();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => $approvedCount . ' produção(ões) aprovada(s) em massa com sucesso.',
            'timer' => 2600,
        ]);
    }

    public function reject(): void
    {
        if (!$this->selectedCycle || !$this->selectedProduction) {
            return;
        }

        // Evita bloquear a reprovação por erros antigos (ex.: requiresSapRelease).
        $this->resetValidation();

        $pendingRows = collect($this->findingRows)
            ->reject(fn ($row) => (bool) ($row['is_conform'] ?? false))
            ->values();

        if ($pendingRows->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma pendência para reprovar',
                'html' => 'Todos os itens foram marcados como conformes. Para reprovar, deixe ao menos um item pendente.',
                'timer' => 2800,
            ]);
            return;
        }

        $this->validate([
            'analystNote' => 'nullable|string|max:5000',
        ]);

        $subcategoriesById = $this->subcategories->keyBy(fn ($subcategory) => (int) data_get($subcategory, 'id'));
        $validItemIdsBySubcategory = $subcategoriesById->map(function ($subcategory) {
            $items = data_get($subcategory, 'Items', data_get($subcategory, 'items', []));
            return collect($items)->pluck('id')->map(fn ($id) => (int) $id)->flip();
        });

        $pendingSubcategoryIds = $pendingRows
            ->pluck('subcategory_id')
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $dbSubcategoryIds = ProjectReviewSubcategory::query()
            ->whereIn('id', $pendingSubcategoryIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $pendingItemIds = $pendingRows
            ->pluck('item_id')
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $dbItemsById = ProjectReviewItem::query()
            ->whereIn('id', $pendingItemIds->all())
            ->get(['id', 'subcategory_id'])
            ->keyBy('id');

        $uniqueByPointLabel = $this->hasPointLabelColumn();
        $seen = [];
        foreach ($pendingRows as $index => $row) {
            $rowIndex = (int) $index;
            $pointLabel = $this->normalizePointLabel($row['point_label'] ?? '');

            if ($pointLabel === '') {
                $this->addError("findingRows.{$rowIndex}.point_label", 'Informe a ref: (agrupador).');
            }

            if (empty($row['subcategory_id'])) {
                $this->addError("findingRows.{$rowIndex}.subcategory_id", 'Subcategoria inválida.');
                continue;
            }

            $subcategoryId = (int) $row['subcategory_id'];
            $subcategoryExists = $subcategoriesById->has($subcategoryId) || $dbSubcategoryIds->has($subcategoryId);
            if (!$subcategoryExists) {
                $this->addError("findingRows.{$rowIndex}.subcategory_id", 'Subcategoria não encontrada.');
            }

            if (!empty($row['item_id'])) {
                $itemId = (int) $row['item_id'];
                $allowedItems = $validItemIdsBySubcategory->get($subcategoryId);
                $itemIsAllowedByActiveTaxonomy = $allowedItems && $allowedItems->has($itemId);
                $dbItem = $dbItemsById->get($itemId);
                $itemIsValidByDatabase = $dbItem && (int) $dbItem->subcategory_id === $subcategoryId;

                if (!$itemIsAllowedByActiveTaxonomy && !$itemIsValidByDatabase) {
                    $this->addError("findingRows.{$rowIndex}.item_id", 'Item não encontrado para a subcategoria selecionada.');
                }
            }

            if (!in_array((string) ($row['origin'] ?? ''), ['LEVANTAMENTO', 'PROJETO', 'AMBOS'], true)) {
                $this->addError("findingRows.{$rowIndex}.origin", 'Origem inválida.');
            }

            if (!empty($row['action_type']) && !in_array((string) $row['action_type'], ['FALTA', 'ADICIONAR', 'REMOVER', 'ALTERAR'], true)) {
                $this->addError("findingRows.{$rowIndex}.action_type", 'Movimento inválido.');
            }

            if (!is_null($row['quantity']) && ((int) $row['quantity'] < 1)) {
                $this->addError("findingRows.{$rowIndex}.quantity", 'Quantidade inválida.');
            }

            if (!empty($row['item_id']) && empty($row['action_type'])) {
                $this->addError("findingRows.{$rowIndex}.action_type", 'Selecione FALTA/ADICIONAR/REMOVER/ALTERAR antes de adicionar item.');
            }

            if (!empty($row['item_id']) && empty($row['quantity'])) {
                $this->addError("findingRows.{$rowIndex}.quantity", 'Informe a quantidade.');
            }

            $key = (string) $row['subcategory_id']
                . ':' . (string) ($row['item_id'] ?? 'null')
                . ':' . (string) ($row['origin'] ?? 'PROJETO')
                . ':' . (string) ($row['action_type'] ?? '');
            if ($uniqueByPointLabel) {
                // Com ponto de referência habilitado, a unicidade considera também a ref.
                $key .= ':' . $pointLabel;
            }
            if (isset($seen[$key]) && !empty($row['item_id'])) {
                $this->addError(
                    "findingRows.{$rowIndex}.item_id",
                    $uniqueByPointLabel
                        ? 'Item duplicado: mesma subcategoria, origem, ação e ref nesta análise.'
                        : 'Item duplicado: mesma subcategoria, origem e ação nesta análise.'
                );
            }
            $seen[$key] = true;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $firstError = (string) collect($this->getErrorBag()->all())->first();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Não foi possível reprovar',
                'html' => $firstError !== '' ? $firstError : 'Existem inconsistências na análise. Revise os itens e tente novamente.',
                'timer' => 4200,
            ]);
            return;
        }

        DB::transaction(function () {
            $this->selectedCycle->Findings()->delete();

            $rowsToPersist = collect($this->findingRows)->reject(fn ($row) => (bool) ($row['is_conform'] ?? false))->values();
            foreach ($rowsToPersist as $row) {
                $payload = [
                    'subcategory_id' => (int) $row['subcategory_id'],
                    'item_id' => empty($row['item_id']) ? null : (int) $row['item_id'],
                    'origin' => (string) $row['origin'],
                    'action_type' => $row['action_type'] ?? null,
                    'quantity' => empty($row['quantity']) ? null : (int) $row['quantity'],
                    'note' => trim((string) ($row['note'] ?? '')) ?: null,
                ];

                if ($this->hasPointLabelColumn()) {
                    $payload['point_label'] = $this->normalizePointLabel($row['point_label'] ?? '');
                }

                $this->selectedCycle->Findings()->create($payload);
            }

            $this->selectedCycle->update([
                'decision' => 'REJECTED',
                'decided_by' => auth()->id(),
                'decided_at' => now(),
                'analyst_note' => trim($this->analystNote) ?: null,
            ]);

            $this->selectedProduction->update([
                'status' => Production::STATUS_REJECTED_PROJECT_REVIEW,
            ]);

            Notetimeline::create([
                'note_id' => $this->selectedProduction->note_id,
                'service_id' => $this->selectedProduction->service_id,
                'production_id' => $this->selectedProduction->id,
                'user_id' => auth()->id(),
                'info' => 'Projeto reprovado na Análise de Projeto.',
                'status' => Production::STATUS_REJECTED_PROJECT_REVIEW,
            ]);

            if ($this->selectedProduction->User) {
                $this->selectedProduction->User->notify(new SystemNotification(
                    titulo: 'Projeto Reprovado na Análise',
                    mensagem: 'A nota <strong>' . $this->selectedProduction->Note->note . '</strong> foi reprovada. Clique para abrir a conversa da análise.',
                    link: $this->buildProjectReviewLinkForRecipient($this->selectedProduction->User, $this->selectedProduction),
                    status: 4,
                    extras: []
                ));
            }

            $this->clearDraft();
        });

        $this->closeModalSuccess('Projeto reprovado com sucesso.');
    }

    public function addReply(): void
    {
        if (!$this->selectedProduction || !$this->selectedCycle) {
            return;
        }

        $message = trim($this->newReply);
        if ($message === '') {
            return;
        }

        ProjectReviewMessage::create([
            'production_id' => $this->selectedProduction->id,
            'cycle_id' => $this->selectedCycle->id,
            'user_id' => auth()->id(),
            'message' => $message,
        ]);

        if ($this->selectedProduction->User && $this->selectedProduction->User->id !== auth()->id()) {
            $this->selectedProduction->User->notify(new SystemNotification(
                titulo: 'Novo comentário na Análise de Projeto',
                mensagem: 'O analista comentou na nota <strong>' . ($this->selectedProduction->Note->note ?? '-') . '</strong>. Clique para abrir o chat.',
                link: $this->buildProjectReviewLinkForRecipient($this->selectedProduction->User, $this->selectedProduction),
                status: 2,
                extras: []
            ));
        }

        $this->newReply = '';
        $this->refreshSelectedProductionMessages();
    }

    public function getReviewMessagesProperty()
    {
        if (!$this->selectedProduction) {
            return collect();
        }

        return collect($this->selectedProduction->ProjectReviewMessages ?? [])
            ->sortBy(function ($message) {
                return sprintf(
                    '%s-%010d',
                    optional($message->created_at)->format('Y-m-d H:i:s.u') ?? '',
                    (int) ($message->id ?? 0)
                );
            })
            ->values();
    }

    private function refreshSelectedProductionMessages(): void
    {
        if (!$this->selectedProduction) {
            return;
        }

        $messages = ProjectReviewMessage::query()
            ->with('User')
            ->where('production_id', $this->selectedProduction->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->selectedProduction->setRelation('ProjectReviewMessages', $messages);
    }

    private function resolveCycle(string $decision): void
    {
        if (!$this->selectedCycle || !$this->selectedProduction) {
            return;
        }

        $this->resetValidation();

        $rules = [
            'analystNote' => 'nullable|string|max:5000',
        ];

        if ($decision === 'APPROVED_WITH_REMARKS') {
            $rules['analystNote'] = 'required|string|min:5|max:5000';
        }
        if (in_array($decision, ['APPROVED', 'APPROVED_WITH_REMARKS'], true)) {
            $rules['requiresSapRelease'] = 'required|in:SIM,NAO';
        }

        $this->validate($rules);
        $requiresSapRelease = $this->requiresSapRelease === 'SIM';

        DB::transaction(function () use ($decision, $requiresSapRelease) {
            $this->selectedCycle->update([
                'decision' => $decision,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
                'analyst_note' => trim($this->analystNote) ?: null,
            ]);

            $this->selectedProduction->update([
                'status' => $requiresSapRelease ? Production::STATUS_RELEASED_TO_FINISH : 5,
            ]);

            Notetimeline::create([
                'note_id' => $this->selectedProduction->note_id,
                'service_id' => $this->selectedProduction->service_id,
                'production_id' => $this->selectedProduction->id,
                'user_id' => auth()->id(),
                'info' => $requiresSapRelease
                    ? 'Projeto aprovado na Análise de Projeto e liberado para finalização no SAP.'
                    : ($decision === 'APPROVED_WITH_REMARKS'
                        ? 'Projeto aprovado com ressalvas na Análise de Projeto.'
                        : 'Projeto aprovado na Análise de Projeto.'),
                'status' => $requiresSapRelease ? Production::STATUS_RELEASED_TO_FINISH : 5,
            ]);

            if ($this->selectedProduction->User) {
                $this->selectedProduction->User->notify(new SystemNotification(
                    titulo: $requiresSapRelease ? 'Projeto Liberado para Finalização no SAP' : 'Projeto Aprovado na Análise',
                    mensagem: $requiresSapRelease
                        ? 'A nota <strong>' . $this->selectedProduction->Note->note . '</strong> foi liberada para finalização no SAP.'
                        : 'A nota <strong>' . $this->selectedProduction->Note->note . '</strong> foi aprovada na análise de projeto.',
                    link: $this->buildProjectReviewLinkForRecipient($this->selectedProduction->User, $this->selectedProduction),
                    status: 1,
                    extras: []
                ));
            }

            $this->clearDraft();
        });

        $this->closeModalSuccess(
            $requiresSapRelease
                ? 'Projeto liberado para finalização no SAP com sucesso.'
                : 'Projeto aprovado com sucesso.'
        );
    }

    private function closeModalSuccess(string $message): void
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => $message,
            'timer' => 2500,
        ]);

        $this->resetReviewForm();
    }

    private function resetReviewForm(): void
    {
        $this->selectedProduction = null;
        $this->drawingProduction = null;
        $this->selectedCycle = null;
        $this->analystNote = '';
        $this->requiresSapRelease = '';
        $this->findingRows = [];
        $this->newReply = '';
        $this->selectedCategoryId = null;
        $this->selectedSubcategoryId = null;
        $this->selectedPointLabel = 'P1';
        $this->selectedPointFilter = '';
        $this->pointRenameInputs = [];
        $this->duplicateMode = '';
        $this->duplicateReference = '';
        $this->duplicatePointLabel = '';
        $this->selectedOrigin = 'PROJETO';
        $this->selectedActionType = 'FALTA';
        $this->collapsedGroups = [];
        $this->collapsedCategories = [];
        $this->collapsedSubcategories = [];
        $this->draftSavedAt = null;
        $this->resetValidation();
    }

    private function resolveDrawingProduction(Production $production): ?Production
    {
        $serviceName = mb_strtolower((string) ($production->Service->service ?? ''));
        if (str_contains($serviceName, 'desenho')) {
            return $production->loadMissing('Files', 'Service', 'Note');
        }

        $drawing = Production::query()
            ->with(['Files', 'Service', 'Note'])
            ->where('note_id', $production->note_id)
            ->whereHas('Service', function ($q) {
                $q->whereRaw('LOWER(service) like ?', ['%desenho%']);
            })
            ->latest('id')
            ->first();

        return $drawing ?: $production->loadMissing('Files', 'Service', 'Note');
    }

    public function downloadProductionFile(int $fileId)
    {
        if (!$this->selectedProduction) {
            return null;
        }

        $file = File::query()
            ->where('id', $fileId)
            ->where('note_id', $this->selectedProduction->note_id)
            ->first();

        if (!$file) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Arquivo não encontrado',
                'html' => 'O arquivo selecionado não está disponível para esta nota.',
                'timer' => 2600,
            ]);
            return null;
        }

        if (!$file->path || !Storage::exists($file->path)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Arquivo indisponível',
                'html' => 'Não foi possível localizar o arquivo no storage. Atualize a lista e tente novamente.',
                'timer' => 3200,
            ]);
            return null;
        }

        $downloadName = $file->original_name ?: ($file->file_name . ($file->ext ? '.' . $file->ext : ''));
        try {
            return Storage::download($file->path, $downloadName);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Erro ao baixar arquivo',
                'html' => 'O arquivo não pôde ser lido no storage.',
                'timer' => 3200,
            ]);
            return null;
        }
    }

    public function downloadFile(int $fileId)
    {
        return $this->downloadProductionFile($fileId);
    }

    private function clearBulkSelection(): void
    {
        $this->selectPage = false;
        $this->selectedProductionIds = [];
    }

    private function syncDraftFlagsForPage($paginator): void
    {
        $userId = auth()->id();
        if (!$userId) {
            $this->draftProductionIds = [];
            return;
        }

        $productionIds = collect($paginator->items())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (empty($productionIds)) {
            $this->draftProductionIds = [];
            return;
        }

        $this->draftProductionIds = ProjectReviewDraft::query()
            ->where('user_id', $userId)
            ->whereIn('production_id', $productionIds)
            ->whereHas('Cycle', function ($q) {
                $q->where('decision', 'PENDING');
            })
            ->distinct()
            ->pluck('production_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function restoreDraft(): void
    {
        if (!$this->selectedProduction || !$this->selectedCycle || !auth()->id()) {
            return;
        }

        $draft = ProjectReviewDraft::query()
            ->where('production_id', $this->selectedProduction->id)
            ->where('cycle_id', $this->selectedCycle->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$draft) {
            // Fallback: quando o ciclo mudou, reaproveita o rascunho mais recente da produção do mesmo usuário.
            $draft = ProjectReviewDraft::query()
                ->where('production_id', $this->selectedProduction->id)
                ->where('user_id', auth()->id())
                ->latest('updated_at')
                ->first();
        }

        if (!$draft) {
            return;
        }

        $payload = (array) ($draft->payload ?? []);

        $this->findingRows = is_array($payload['findingRows'] ?? null) ? $payload['findingRows'] : $this->findingRows;
        $this->findingRows = collect($this->findingRows)->map(function ($row) {
            if (!is_array($row)) {
                return $row;
            }
            $row['point_label'] = $this->normalizePointLabel($row['point_label'] ?? '');
            return $row;
        })->all();
        $this->analystNote = (string) ($payload['analystNote'] ?? $this->analystNote);
        $this->collapsedGroups = is_array($payload['collapsedGroups'] ?? null) ? $payload['collapsedGroups'] : $this->collapsedGroups;
        $this->collapsedCategories = is_array($payload['collapsedCategories'] ?? null) ? $payload['collapsedCategories'] : $this->collapsedCategories;
        $this->collapsedSubcategories = is_array($payload['collapsedSubcategories'] ?? null) ? $payload['collapsedSubcategories'] : $this->collapsedSubcategories;
        $this->selectedCategoryId = isset($payload['selectedCategoryId']) ? (int) $payload['selectedCategoryId'] : $this->selectedCategoryId;
        $this->selectedSubcategoryId = isset($payload['selectedSubcategoryId']) ? (int) $payload['selectedSubcategoryId'] : $this->selectedSubcategoryId;
        $this->selectedPointLabel = $this->normalizePointLabel((string) ($payload['selectedPointLabel'] ?? $this->selectedPointLabel));
        $this->selectedPointFilter = $this->normalizePointFilter((string) ($payload['selectedPointFilter'] ?? $this->selectedPointFilter));
        if (
            $this->selectedPointFilter === 'SEM PONTO'
            && !collect($this->findingRows)->contains(fn ($row) => $this->normalizePointLabel($row['point_label'] ?? '') === 'SEM PONTO')
        ) {
            $this->selectedPointFilter = '';
        }
        $this->selectedOrigin = (string) ($payload['selectedOrigin'] ?? $this->selectedOrigin);
        $this->selectedActionType = (string) ($payload['selectedActionType'] ?? $this->selectedActionType);
        $this->draftSavedAt = optional($draft->updated_at)->format('d/m/Y H:i:s');
    }

    private function persistDraft(): bool
    {
        if (!$this->selectedProduction || !$this->selectedCycle || !auth()->id()) {
            return false;
        }

        if ($this->selectedCycle->decision !== 'PENDING') {
            return false;
        }

        $payload = [
            'findingRows' => array_values(collect($this->findingRows)->map(function ($row) {
                if (!is_array($row)) {
                    return $row;
                }
                $row['point_label'] = $this->normalizePointLabel($row['point_label'] ?? '');
                return $row;
            })->all()),
            'analystNote' => $this->analystNote,
            'collapsedGroups' => $this->collapsedGroups,
            'collapsedCategories' => $this->collapsedCategories,
            'collapsedSubcategories' => $this->collapsedSubcategories,
            'selectedCategoryId' => $this->selectedCategoryId,
            'selectedSubcategoryId' => $this->selectedSubcategoryId,
            'selectedPointLabel' => $this->normalizePointLabel($this->selectedPointLabel),
            'selectedPointFilter' => $this->normalizePointFilter($this->selectedPointFilter),
            'selectedOrigin' => $this->selectedOrigin,
            'selectedActionType' => $this->selectedActionType,
        ];

        ProjectReviewDraft::query()->updateOrCreate(
            [
                'production_id' => $this->selectedProduction->id,
                'cycle_id' => $this->selectedCycle->id,
                'user_id' => auth()->id(),
            ],
            [
                'payload' => $payload,
            ]
        );

        $this->draftSavedAt = now()->format('d/m/Y H:i:s');

        return true;
    }

    private function clearDraft(): void
    {
        if (!$this->selectedProduction || !$this->selectedCycle || !auth()->id()) {
            return;
        }

        ProjectReviewDraft::query()
            ->where('production_id', $this->selectedProduction->id)
            ->where('cycle_id', $this->selectedCycle->id)
            ->where('user_id', auth()->id())
            ->delete();
    }

    private function buildProjectReviewLinkForRecipient(User $recipient, Production $production): string
    {
        $targetProduction = $this->resolveRecipientProductionForUserArea($recipient, $production);
        $isOwnerRecipient = (string) $targetProduction->user_id === (string) $recipient->id;

        if ($isOwnerRecipient) {
            return route('services.production', [
                'service' => $targetProduction->service_id,
                'prod' => $targetProduction->id,
                'open_project_review' => 1,
                'production' => $targetProduction->id,
                'note' => $targetProduction->note_id,
                'focus' => 'chat',
            ]);
        }

        if ($recipient->can('analyst')) {
            return route('project_review.list', [
                'production' => $production->id,
                'focus' => 'chat',
            ]);
        }

        return route('services.main', [
            'service' => $production->service_id,
        ]);
    }

    private function resolveRecipientProductionForUserArea(User $recipient, Production $production): Production
    {
        if ((string) $production->user_id === (string) $recipient->id) {
            return $production;
        }

        $recipientProduction = Production::query()
            ->where('note_id', $production->note_id)
            ->where('user_id', $recipient->id)
            ->whereHas('Service', function ($q) {
                $q->where(function ($serviceQuery) {
                    $serviceQuery->where('folder', 'desenho')
                        ->orWhereRaw('LOWER(service) like ?', ['%desenho%']);
                });
            })
            ->latest('id')
            ->first();

        return $recipientProduction ?: $production;
    }

    private function normalizePointLabel(?string $value): string
    {
        $label = trim((string) $value);
        if ($label === '') {
            return 'SEM PONTO';
        }

        return mb_substr(mb_strtoupper($label, 'UTF-8'), 0, 120);
    }

    private function normalizePointFilter(?string $value): string
    {
        $filter = trim((string) $value);
        if ($filter === '') {
            return '';
        }

        return $this->normalizePointLabel($filter);
    }

    private function hasPointLabelColumn(): bool
    {
        static $hasColumn = null;
        if (!is_null($hasColumn)) {
            return $hasColumn;
        }

        try {
            $hasColumn = Schema::hasTable('project_review_findings')
                && Schema::hasColumn('project_review_findings', 'point_label');
        } catch (\Throwable $e) {
            report($e);
            $hasColumn = false;
        }

        return $hasColumn;
    }

    public function render()
    {
        $lists = $this->lists;

        return view('livewire.project-review.queue', [
            'lists' => $lists,
            'companies' => $this->companies,
            'categories' => $this->categories,
            'subcategories' => $this->subcategories,
            'availableSubcategories' => $this->availableSubcategories,
            'availableItems' => $this->availableItems,
            'findingsTree' => $this->findingsTree,
        ]);
    }

    private function loadTaxonomy(): void
    {
        $this->taxonomySubcategories = ProjectReviewSubcategory::query()
            ->with([
                'Category:id,name,active',
                'Items' => function ($q) {
                    $q->select('id', 'subcategory_id', 'name', 'active')
                        ->orderBy('name');
                },
            ])
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->all();

        $this->taxonomyCategories = ProjectReviewCategory::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->all();
    }
}
