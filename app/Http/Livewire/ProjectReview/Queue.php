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
    public string $tab = 'pending';
    public string $mode = 'pending';
    public bool $selectPage = false;
    public array $selectedProductionIds = [];

    public ?Production $selectedProduction = null;
    public ?Production $drawingProduction = null;
    public ?ProjectReviewCycle $selectedCycle = null;

    public string $analystNote = '';
    public array $findingRows = [];
    public string $newReply = '';
    public ?int $selectedCategoryId = null;
    public ?int $selectedSubcategoryId = null;
    public string $selectedOrigin = 'PROJETO';
    public string $selectedActionType = 'FALTA';
    public array $collapsedGroups = [];
    public array $collapsedCategories = [];
    public array $collapsedSubcategories = [];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'savedFiles' => 'onFilesSaved',
    ];

    public function mount(string $mode = 'pending'): void
    {
        $this->mode = $mode;
        $this->tab = $mode === 'history' ? 'history' : 'pending';
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

        return $query->orderByDesc('id')->paginate(30);
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
            $query->where('status', Production::STATUS_IN_PROJECT_REVIEW)
                ->where('completed', false);
        } else {
            $query->whereIn('status', [5, Production::STATUS_REJECTED_PROJECT_REVIEW])
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

        return $query;
    }

    private function exportFilters(): array
    {
        return [
            'search' => $this->search,
            'company_id' => $this->company_id,
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
        return ProjectReviewSubcategory::with('Category', 'Items')
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getCategoriesProperty()
    {
        return ProjectReviewCategory::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getAvailableSubcategoriesProperty()
    {
        if (!$this->selectedCategoryId) {
            return collect();
        }

        return ProjectReviewSubcategory::query()
            ->where('active', true)
            ->where('category_id', $this->selectedCategoryId)
            ->with('Category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getAvailableItemsProperty()
    {
        if (!$this->selectedSubcategoryId) {
            return collect();
        }

        return ProjectReviewItem::query()
            ->where('active', true)
            ->where('subcategory_id', $this->selectedSubcategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getFindingsTreeProperty()
    {
        $subcategories = $this->subcategories->keyBy('id');

        $flat = collect($this->findingRows)
            ->map(function ($row, $index) use ($subcategories) {
                $subcategory = $subcategories->get((int) ($row['subcategory_id'] ?? 0));

                return [
                    'index' => $index,
                    'subcategory_id' => (int) ($row['subcategory_id'] ?? 0),
                    'subcategory_name' => $subcategory?->name ?? 'Subcategoria não encontrada',
                    'category_name' => $subcategory?->Category?->name ?? 'Sem categoria',
                    'item_id' => $row['item_id'] ?? null,
                    'item_name' => $row['item_name'] ?? ($subcategory?->Items?->firstWhere('id', (int) ($row['item_id'] ?? 0))?->name ?? null),
                    'origin' => (string) ($row['origin'] ?? 'PROJETO'),
                    'action_type' => $row['action_type'] ?? null,
                    'quantity' => $row['quantity'] ?? null,
                    'note' => $row['note'] ?? null,
                    'category_key' => 'cat_' . md5((string) ($subcategory?->Category?->name ?? 'sem-categoria')),
                    'subcategory_key' => 'sub_' . (int) ($row['subcategory_id'] ?? 0),
                ];
            })
            ->values();

        return $flat
            ->groupBy('category_name')
            ->map(function ($categoryRows, $categoryName) {
                return [
                    'category_name' => $categoryName,
                    'category_key' => 'cat_' . md5((string) $categoryName),
                    'subcategories' => $categoryRows
                        ->groupBy('subcategory_key')
                        ->map(function ($subRows) {
                            $first = $subRows->first();
                            return [
                                'subcategory_name' => $first['subcategory_name'],
                                'subcategory_key' => $first['subcategory_key'],
                                'origin' => $first['origin'],
                                'rows' => $subRows->values()->all(),
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

        $this->dispatchBrowserEvent('showModal', ['id' => 'projectReviewModal']);
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
                && (string) ($row['action_type'] ?? '') === $this->selectedActionType;
        });

        if ($alreadyExists) {
            return;
        }

        $this->findingRows[] = [
            'subcategory_id' => (int) $this->selectedSubcategoryId,
            'item_id' => $itemId,
            'item_name' => $item->name,
            'origin' => $this->selectedOrigin,
            'action_type' => $this->selectedActionType,
            'quantity' => 1,
            'note' => '',
            'is_conform' => false,
        ];
    }

    public function setGroupOrigin(string $groupKey, string $origin): void
    {
        if (!in_array($origin, ['LEVANTAMENTO', 'PROJETO', 'AMBOS'], true)) {
            return;
        }

        foreach ($this->findingRows as $i => $row) {
            if ('sub_' . (int) ($row['subcategory_id'] ?? 0) === $groupKey) {
                $this->findingRows[$i]['origin'] = $origin;
            }
        }
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
                $rowCategoryKey = 'cat_' . md5((string) ($subcategory?->Category?->name ?? 'sem-categoria'));
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
            ->where('completed', false)
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
                    'completed' => true,
                    'completed_at' => now(),
                    'confirmed' => false,
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

        $pendingRows = collect($this->findingRows)
            ->reject(fn ($row) => (bool) ($row['is_conform'] ?? false))
            ->values()
            ->all();

        if (count($pendingRows) === 0) {
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

        $seen = [];
        foreach ($this->findingRows as $index => $row) {
            if ((bool) ($row['is_conform'] ?? false)) {
                continue;
            }

            if (empty($row['subcategory_id'])) {
                $this->addError("findingRows.{$index}.subcategory_id", 'Subcategoria inválida.');
                continue;
            }

            if (!ProjectReviewSubcategory::query()->where('id', (int) $row['subcategory_id'])->exists()) {
                $this->addError("findingRows.{$index}.subcategory_id", 'Subcategoria não encontrada.');
            }

            if (!empty($row['item_id']) && !ProjectReviewItem::query()->where('id', (int) $row['item_id'])->exists()) {
                $this->addError("findingRows.{$index}.item_id", 'Item não encontrado.');
            }

            if (!in_array((string) ($row['origin'] ?? ''), ['LEVANTAMENTO', 'PROJETO', 'AMBOS'], true)) {
                $this->addError("findingRows.{$index}.origin", 'Origem inválida.');
            }

            if (!empty($row['action_type']) && !in_array((string) $row['action_type'], ['FALTA', 'ADICIONAR', 'REMOVER'], true)) {
                $this->addError("findingRows.{$index}.action_type", 'Movimento inválido.');
            }

            if (!is_null($row['quantity']) && ((int) $row['quantity'] < 1)) {
                $this->addError("findingRows.{$index}.quantity", 'Quantidade inválida.');
            }

            if (!empty($row['item_id']) && empty($row['action_type'])) {
                $this->addError("findingRows.{$index}.action_type", 'Selecione FALTA/ADICIONAR/REMOVER antes de adicionar item.');
            }

            if (!empty($row['item_id']) && empty($row['quantity'])) {
                $this->addError("findingRows.{$index}.quantity", 'Informe a quantidade.');
            }

            $key = (string) $row['subcategory_id'] . ':' . (string) ($row['item_id'] ?? 'null');
            if (isset($seen[$key]) && !empty($row['item_id'])) {
                $this->addError("findingRows.{$index}.item_id", 'Item duplicado na mesma subcategoria nesta análise.');
            }
            $seen[$key] = true;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () {
            $this->selectedCycle->Findings()->delete();

            $rowsToPersist = collect($this->findingRows)
                ->reject(fn ($row) => (bool) ($row['is_conform'] ?? false))
                ->values();

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
                'completed' => false,
                'completed_at' => null,
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
                    mensagem: 'A nota <strong>' . $this->selectedProduction->Note->note . '</strong> foi reprovada e retornou para correção.',
                    link: route('services.accompany', ['service' => $this->selectedProduction->service_id]),
                    status: 4,
                    extras: []
                ));
            }
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
                mensagem: 'O analista comentou na nota <strong>' . ($this->selectedProduction->Note->note ?? '-') . '</strong>.',
                link: route('services.production', ['service' => $this->selectedProduction->service_id, 'prod' => $this->selectedProduction->id]),
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

        $rules = [
            'analystNote' => 'nullable|string|max:5000',
        ];

        if ($decision === 'APPROVED_WITH_REMARKS') {
            $rules['analystNote'] = 'required|string|min:5|max:5000';
        }

        $this->validate($rules);

        DB::transaction(function () use ($decision) {
            $this->selectedCycle->update([
                'decision' => $decision,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
                'analyst_note' => trim($this->analystNote) ?: null,
            ]);

            $this->selectedProduction->update([
                'status' => 5,
                'completed' => true,
                'completed_at' => now(),
                'confirmed' => false,
            ]);

            Notetimeline::create([
                'note_id' => $this->selectedProduction->note_id,
                'service_id' => $this->selectedProduction->service_id,
                'production_id' => $this->selectedProduction->id,
                'user_id' => auth()->id(),
                'info' => $decision === 'APPROVED_WITH_REMARKS'
                    ? 'Projeto aprovado com ressalvas na Análise de Projeto.'
                    : 'Projeto aprovado na Análise de Projeto.',
                'status' => 5,
            ]);

            if ($this->selectedProduction->User) {
                $this->selectedProduction->User->notify(new SystemNotification(
                    titulo: 'Projeto Aprovado na Análise',
                    mensagem: 'A nota <strong>' . $this->selectedProduction->Note->note . '</strong> foi aprovada na análise de projeto.',
                    link: route('services.accompany', ['service' => $this->selectedProduction->service_id]),
                    status: 1,
                    extras: []
                ));
            }
        });

        $this->closeModalSuccess('Projeto aprovado com sucesso.');
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
        $this->findingRows = [];
        $this->newReply = '';
        $this->selectedCategoryId = null;
        $this->selectedSubcategoryId = null;
        $this->selectedOrigin = 'PROJETO';
        $this->selectedActionType = 'FALTA';
        $this->collapsedGroups = [];
        $this->collapsedCategories = [];
        $this->collapsedSubcategories = [];
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

    public function render()
    {
        return view('livewire.project-review.queue', [
            'lists' => $this->lists,
            'companies' => $this->companies,
            'categories' => $this->categories,
            'subcategories' => $this->subcategories,
            'availableSubcategories' => $this->availableSubcategories,
            'availableItems' => $this->availableItems,
            'findingsTree' => $this->findingsTree,
        ]);
    }
}
