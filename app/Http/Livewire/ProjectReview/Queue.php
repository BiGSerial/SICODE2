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
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Queue extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $company_id = '';
    public string $cost_share_filter = '';
    public string $tab = 'pending';
    public string $mode = 'pending';
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
    public string $selectedOrigin = 'PROJETO';
    public string $selectedActionType = 'FALTA';
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
            ->orderBy('id', 'asc')
            ->paginate(30);

        $this->syncDraftFlagsForPage($lists);

        return $lists;
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

        if ($this->company_id !== '') {
            $query->where('company_id', $this->company_id);
        }

        $costFilter = $this->cost_share_filter;
        if (in_array($costFilter, ['client_51', 'company_51', 'both_51'], true)) {
            $query->whereHas('ProjectReviewCycles.Orders', function ($orderQuery) use ($costFilter) {
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

        return $query;
    }

    private function exportFilters(): array
    {
        return [
            'search' => $this->search,
            'company_id' => $this->company_id,
            'cost_share_filter' => $this->cost_share_filter,
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

                return [
                    'index' => $index,
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
                    'subcategory_key' => 'sub_' . (int) ($row['subcategory_id'] ?? 0),
                ];
            })
            ->values();

        return $flat
            ->groupBy('category_name')
            ->map(function ($categoryRows, $categoryName) use ($originSort) {
                return [
                    'category_name' => $categoryName,
                    'category_key' => 'cat_' . md5((string) $categoryName),
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
            ->all();
    }

    public function updatedSelectedCategoryId(): void
    {
        $this->selectedSubcategoryId = null;
    }

    public function openReview(int $productionId): void
    {
        $this->resetReviewForm();

        $this->selectedProduction = Production::with([
            'Note',
            'Note.Files.Service',
            'User',
            'Company',
            'Service',
            'Files',
            'Analise',
            'ProjectReviewCycles' => function ($q) {
                $q->with([
                    'Orders',
                    'Findings.Subcategory.Category',
                    'Findings.Item',
                    'DecidedBy',
                    'Messages.User',
                ])->latest('round_number');
            },
        ])->findOrFail($productionId);

        $this->selectedCycle = $this->selectedProduction->ProjectReviewCycles
            ->firstWhere('decision', 'PENDING')
            ?: $this->selectedProduction->ProjectReviewCycles->first();

        $this->drawingProduction = $this->resolveDrawingProduction($this->selectedProduction);

        if ($this->selectedCycle) {
            $this->findingRows = $this->selectedCycle->Findings->map(function ($f) {
                return [
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
                    $this->findingRows = $previousRejectedCycle->Findings->map(function ($f) {
                        return [
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

        $this->dispatchBrowserEvent('showModal', ['id' => 'projectReviewModal']);
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

        $this->findingRows[] = [
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

        $item = $this->availableItems->firstWhere('id', $itemId);
        if (!$item) {
            return;
        }

        $alreadyExists = collect($this->findingRows)->contains(function ($row) use ($itemId) {
            return (int) ($row['subcategory_id'] ?? 0) === (int) $this->selectedSubcategoryId
                && (int) ($row['item_id'] ?? 0) === $itemId
                && (string) ($row['origin'] ?? 'PROJETO') === $this->selectedOrigin
                && (string) ($row['action_type'] ?? '') === $this->selectedActionType;
        });

        if ($alreadyExists) {
            return;
        }

        $this->findingRows[] = [
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

    public function removeSubcategoryGroup(string $subcategoryKey): void
    {
        $this->findingRows = collect($this->findingRows)
            ->reject(function ($row) use ($subcategoryKey) {
                return 'sub_' . (int) ($row['subcategory_id'] ?? 0) === $subcategoryKey;
            })
            ->values()
            ->all();
    }

    public function removeCategoryGroup(string $categoryKey): void
    {
        $subcategoriesById = $this->subcategories->keyBy('id');

        $this->findingRows = collect($this->findingRows)
            ->reject(function ($row) use ($categoryKey, $subcategoriesById) {
                $subcategory = $subcategoriesById->get((int) ($row['subcategory_id'] ?? 0));
                $rowCategoryName = data_get($subcategory, 'Category.name', data_get($subcategory, 'category.name', 'sem-categoria'));
                $rowCategoryKey = 'cat_' . md5((string) $rowCategoryName);
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

        $seen = [];
        foreach ($pendingRows as $index => $row) {
            $rowIndex = (int) $index;

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

            if (!empty($row['action_type']) && !in_array((string) $row['action_type'], ['FALTA', 'ADICIONAR', 'REMOVER'], true)) {
                $this->addError("findingRows.{$rowIndex}.action_type", 'Movimento inválido.');
            }

            if (!is_null($row['quantity']) && ((int) $row['quantity'] < 1)) {
                $this->addError("findingRows.{$rowIndex}.quantity", 'Quantidade inválida.');
            }

            if (!empty($row['item_id']) && empty($row['action_type'])) {
                $this->addError("findingRows.{$rowIndex}.action_type", 'Selecione FALTA/ADICIONAR/REMOVER antes de adicionar item.');
            }

            if (!empty($row['item_id']) && empty($row['quantity'])) {
                $this->addError("findingRows.{$rowIndex}.quantity", 'Informe a quantidade.');
            }

            $key = (string) $row['subcategory_id']
                . ':' . (string) ($row['item_id'] ?? 'null')
                . ':' . (string) ($row['origin'] ?? 'PROJETO')
                . ':' . (string) ($row['action_type'] ?? '');
            if (isset($seen[$key]) && !empty($row['item_id'])) {
                $this->addError("findingRows.{$rowIndex}.item_id", 'Item duplicado com a mesma ação na mesma subcategoria e origem nesta análise.');
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
                $this->selectedCycle->Findings()->create([
                    'subcategory_id' => (int) $row['subcategory_id'],
                    'item_id' => empty($row['item_id']) ? null : (int) $row['item_id'],
                    'origin' => (string) $row['origin'],
                    'action_type' => $row['action_type'] ?? null,
                    'quantity' => empty($row['quantity']) ? null : (int) $row['quantity'],
                    'note' => trim((string) ($row['note'] ?? '')) ?: null,
                ]);
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
                    link: $this->buildDrawingChatLink($this->selectedProduction),
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
                link: $this->buildDrawingChatLink($this->selectedProduction),
                status: 2,
                extras: []
            ));
        }

        $this->newReply = '';
        $messages = ProjectReviewMessage::query()
            ->with('User')
            ->where('cycle_id', $this->selectedCycle->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        // Mantém árvore e estado da tela em memória; atualiza apenas o chat.
        $this->selectedCycle->setRelation('Messages', $messages);
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
                    link: $this->buildDrawingChatLink($this->selectedProduction),
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
            return;
        }

        $payload = (array) ($draft->payload ?? []);

        $this->findingRows = is_array($payload['findingRows'] ?? null) ? $payload['findingRows'] : $this->findingRows;
        $this->analystNote = (string) ($payload['analystNote'] ?? $this->analystNote);
        $this->collapsedGroups = is_array($payload['collapsedGroups'] ?? null) ? $payload['collapsedGroups'] : $this->collapsedGroups;
        $this->collapsedCategories = is_array($payload['collapsedCategories'] ?? null) ? $payload['collapsedCategories'] : $this->collapsedCategories;
        $this->collapsedSubcategories = is_array($payload['collapsedSubcategories'] ?? null) ? $payload['collapsedSubcategories'] : $this->collapsedSubcategories;
        $this->selectedCategoryId = isset($payload['selectedCategoryId']) ? (int) $payload['selectedCategoryId'] : $this->selectedCategoryId;
        $this->selectedSubcategoryId = isset($payload['selectedSubcategoryId']) ? (int) $payload['selectedSubcategoryId'] : $this->selectedSubcategoryId;
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
            'findingRows' => array_values($this->findingRows),
            'analystNote' => $this->analystNote,
            'collapsedGroups' => $this->collapsedGroups,
            'collapsedCategories' => $this->collapsedCategories,
            'collapsedSubcategories' => $this->collapsedSubcategories,
            'selectedCategoryId' => $this->selectedCategoryId,
            'selectedSubcategoryId' => $this->selectedSubcategoryId,
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

    private function buildDrawingChatLink(Production $production): string
    {
        return route('services.production', [
            'service' => $production->service_id,
            'prod' => $production->id,
            'open_project_review' => 1,
            'production' => $production->id,
            'note' => $production->note_id,
        ]);
    }

    public function render()
    {
        $lists = $this->selectedProduction ? collect() : $this->lists;

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
