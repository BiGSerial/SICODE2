<?php

namespace App\Http\Livewire\ProjectReview;

use App\Jobs\Reports\ExportProjectReviewHistoryListJob;
use App\Models\Company;
use App\Models\File;
use App\Models\Notetimeline;
use App\Models\ProjectReviewCategory;
use App\Models\ProjectReviewCycle;
use App\Models\ProjectReviewMessage;
use App\Models\ProjectReviewSubcategory;
use App\Models\Production;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $company_id = '';
    public ?string $from = null;
    public ?string $to = null;
    public ?ProjectReviewCycle $selectedCycle = null;
    public ?Production $selectedProduction = null;
    public string $selectedHistoryPointFilter = '';
    public bool $editingFindings = false;
    public array $historyFindingRows = [];
    public ?int $selectedCategoryId = null;
    public ?int $selectedSubcategoryId = null;
    public string $selectedPointLabel = 'P1';
    public string $selectedOrigin = 'PROJETO';
    public string $selectedActionType = 'FALTA';
    public string $newReply = '';
    public array $taxonomySubcategories = [];
    public array $taxonomyCategories = [];

    public function mount(): void
    {
        $this->loadTaxonomy();
    }

    public function getRowsProperty()
    {
        $query = Production::query()
            ->select(['id', 'note_id', 'user_id', 'company_id', 'status'])
            ->with([
                'Note:id,note,numPedido,material',
                'User:id,name',
                'Company:id,name',
                'ProjectReviewCycles' => function ($q) {
                    $q->select(['id', 'production_id', 'round_number', 'decision', 'submitted_at', 'decided_by'])
                        ->with([
                            'Orders:id,cycle_id,sort_order,order_number,total_cost,company_cost,client_cost',
                            'DecidedBy:id,name',
                        ])
                        ->latest('round_number');
                },
            ])
            ->whereIn('status', [5, Production::STATUS_REJECTED_PROJECT_REVIEW, Production::STATUS_RELEASED_TO_FINISH])
            ->whereHas('ProjectReviewCycles', function ($q) {
                $q->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED']);
            });

        $this->applyContractScopeToProductions($query);

        return $query
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                $q->whereHas('Note', function ($n) use ($s) {
                    $n->where('note', 'like', $s)
                        ->orWhere('numPedido', 'like', $s)
                        ->orWhere('material', 'like', $s);
                });
            })
            ->when($this->company_id !== '', fn($q) => $q->where('company_id', $this->company_id))
            ->when($this->from, function ($q) {
                $q->whereHas('ProjectReviewCycles', function ($cq) {
                    $cq->where('submitted_at', '>=', $this->from . ' 00:00:00');
                });
            })
            ->when($this->to, function ($q) {
                $q->whereHas('ProjectReviewCycles', function ($cq) {
                    $cq->where('submitted_at', '<=', $this->to . ' 23:59:59');
                });
            })
            ->orderByDesc('id')
            ->paginate(30);
    }

    public function getCompaniesProperty()
    {
        $query = Company::query()->orderBy('name');

        if (auth()->user()?->contract) {
            $query->whereIn('id', $this->allowedCompanyIds()->all());
        }

        return $query->get(['id', 'name']);
    }

    public function exportList(): void
    {
        ExportProjectReviewHistoryListJob::dispatch(
            $this->exportFilters(),
            (string) auth()->id()
        );

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'>
                <p>Seu histórico está sendo gerado.</p>
                <p class='mb-0'><strong>Você será notificado quando o download estiver pronto.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    private function exportFilters(): array
    {
        $companyIds = auth()->user()?->contract ? $this->allowedCompanyIds()->all() : [];

        return [
            'search' => $this->search,
            'company_id' => $this->company_id,
            'from' => $this->from,
            'to' => $this->to,
            'company_ids' => $companyIds,
        ];
    }

    public function openProduction(int $productionId): void
    {
        $query = Production::with([
            'Note.Files.Service',
            'User',
            'Company',
            'Service',
            'Files',
            'Analise',
            'ProjectReviewMessages.User',
            'ProjectReviewCycles' => function ($q) {
                $q->with([
                    'Orders',
                    'Findings.Subcategory.Category',
                    'Findings.Item',
                    'DecidedBy',
                    'Messages' => function ($mq) {
                        $mq->with('User')
                            ->orderBy('created_at')
                            ->orderBy('id');
                    },
                ])->latest('round_number');
            },
        ]);

        $this->applyContractScopeToProductions($query);

        $this->selectedProduction = $query->findOrFail($productionId);

        $this->selectedCycle = $this->selectedProduction->ProjectReviewCycles
            ->firstWhere('decision', 'PENDING')
            ?: $this->selectedProduction->ProjectReviewCycles
            ->firstWhere('decision', 'REJECTED')
            ?: $this->selectedProduction->ProjectReviewCycles->first();
        $this->selectedHistoryPointFilter = '';
        $this->editingFindings = false;
        $this->historyFindingRows = [];
        $this->newReply = '';
        $this->refreshSelectedProductionMessages();

        $this->dispatchBrowserEvent('showModal', ['id' => 'historyProjectReviewModal']);
    }

    private function applyContractScopeToProductions($query): void
    {
        $user = auth()->user();
        if (!$user?->contract) {
            return;
        }

        $companyIds = $this->allowedCompanyIds();
        if ($companyIds->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('company_id', $companyIds->all());
    }

    private function allowedCompanyIds()
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        return collect([$user->company_id])
            ->merge($user->Companies()->pluck('companies.id'))
            ->filter()
            ->unique()
            ->values();
    }

    public function selectCycle(int $cycleId): void
    {
        if (!$this->selectedProduction) {
            return;
        }

        $cycle = collect($this->selectedProduction->ProjectReviewCycles)->firstWhere('id', $cycleId);
        if (!$cycle) {
            return;
        }

        $this->selectedCycle = $cycle;
        $this->selectedHistoryPointFilter = '';
        $this->editingFindings = false;
        $this->historyFindingRows = [];
        $this->newReply = '';
    }

    public function getCanEditSelectedCycleProperty(): bool
    {
        if (!$this->selectedCycle || !$this->selectedProduction || !auth()->check()) {
            return false;
        }

        return !in_array((string) $this->selectedCycle->decision, ['APPROVED', 'APPROVED_WITH_REMARKS'], true)
            && auth()->user()->can('analyst');
    }

    public function getCanReplyProperty(): bool
    {
        if (!$this->selectedCycle || !$this->selectedProduction || !auth()->check()) {
            return false;
        }

        // Encerrado = status 5; demais status permitem continuidade do chat.
        return auth()->user()->can('analyst')
            && (int) $this->selectedProduction->status !== 5;
    }

    public function addReply(): void
    {
        if (!$this->canReply || !$this->selectedProduction || !$this->selectedCycle) {
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

        $this->newReply = '';
        $this->refreshSelectedProductionMessages();
    }

    public function startFindingsEdit(): void
    {
        if (!$this->canEditSelectedCycle) {
            return;
        }

        $this->historyFindingRows = collect($this->selectedCycle->Findings ?? [])
            ->map(function ($f) {
                return [
                    'point_label' => $this->normalizePointLabel((string) ($f->point_label ?? '')),
                    'subcategory_id' => (int) $f->subcategory_id,
                    'subcategory_name' => (string) (optional($f->Subcategory)->name ?? 'Sem subcategoria'),
                    'item_id' => $f->item_id ? (int) $f->item_id : null,
                    'item_name' => (string) (optional($f->Item)->name ?? ''),
                    'origin' => (string) ($f->origin ?: 'PROJETO'),
                    'action_type' => (string) ($f->action_type ?: 'FALTA'),
                    'quantity' => is_null($f->quantity) ? null : (int) $f->quantity,
                    'note' => (string) ($f->note ?? ''),
                ];
            })
            ->values()
            ->all();

        $this->selectedPointLabel = $this->normalizePointLabel(
            (string) (collect($this->historyFindingRows)->pluck('point_label')->filter()->first() ?? 'P1')
        );
        $this->editingFindings = true;
    }

    public function cancelFindingsEdit(): void
    {
        $this->editingFindings = false;
        $this->historyFindingRows = [];
        $this->selectedCategoryId = null;
        $this->selectedSubcategoryId = null;
        $this->selectedPointLabel = 'P1';
        $this->selectedOrigin = 'PROJETO';
        $this->selectedActionType = 'FALTA';
        $this->resetValidation();
    }

    public function updatedSelectedCategoryId(): void
    {
        $this->selectedSubcategoryId = null;
    }

    public function addHistoryEmptySubcategory(): void
    {
        if (!$this->selectedSubcategoryId || !$this->editingFindings || !$this->canEditSelectedCycle) {
            return;
        }

        $subcategory = $this->subcategories->firstWhere('id', (int) $this->selectedSubcategoryId);
        if (!$subcategory) {
            return;
        }

        $this->historyFindingRows[] = [
            'point_label' => $this->normalizePointLabel($this->selectedPointLabel),
            'subcategory_id' => (int) $this->selectedSubcategoryId,
            'subcategory_name' => (string) data_get($subcategory, 'name', 'Sem subcategoria'),
            'item_id' => null,
            'item_name' => '',
            'origin' => $this->selectedOrigin,
            'action_type' => $this->selectedActionType,
            'quantity' => null,
            'note' => '',
        ];
    }

    public function addHistoryItemToFindings(int $itemId): void
    {
        if (!$this->selectedSubcategoryId || !$this->editingFindings || !$this->canEditSelectedCycle) {
            return;
        }

        $subcategory = $this->subcategories->firstWhere('id', (int) $this->selectedSubcategoryId);
        if (!$subcategory) {
            return;
        }

        $item = $this->availableItems->firstWhere('id', $itemId);
        if (!$item) {
            return;
        }

        $pointLabel = $this->normalizePointLabel($this->selectedPointLabel);

        $alreadyExists = collect($this->historyFindingRows)->contains(function ($row) use ($itemId, $pointLabel) {
            return (int) ($row['subcategory_id'] ?? 0) === (int) $this->selectedSubcategoryId
                && (int) ($row['item_id'] ?? 0) === $itemId
                && (string) ($row['origin'] ?? 'PROJETO') === $this->selectedOrigin
                && (string) ($row['action_type'] ?? '') === $this->selectedActionType
                && $this->normalizePointLabel((string) ($row['point_label'] ?? '')) === $pointLabel;
        });

        if ($alreadyExists) {
            return;
        }

        $this->historyFindingRows[] = [
            'point_label' => $pointLabel,
            'subcategory_id' => (int) $this->selectedSubcategoryId,
            'subcategory_name' => (string) data_get($subcategory, 'name', 'Sem subcategoria'),
            'item_id' => (int) $itemId,
            'item_name' => (string) data_get($item, 'name', ''),
            'origin' => $this->selectedOrigin,
            'action_type' => $this->selectedActionType,
            'quantity' => 1,
            'note' => '',
        ];
    }

    public function removeHistoryFindingRow(int $index): void
    {
        if (!isset($this->historyFindingRows[$index])) {
            return;
        }

        unset($this->historyFindingRows[$index]);
        $this->historyFindingRows = array_values($this->historyFindingRows);
    }

    public function saveFindingsEdit(): void
    {
        if (!$this->selectedCycle || !$this->selectedProduction || !$this->canEditSelectedCycle) {
            return;
        }

        $rows = collect($this->historyFindingRows)
            ->map(function ($row) {
                return [
                    'point_label' => $this->normalizePointLabel((string) ($row['point_label'] ?? '')),
                    'subcategory_id' => (int) ($row['subcategory_id'] ?? 0),
                    'item_id' => empty($row['item_id']) ? null : (int) $row['item_id'],
                    'origin' => (string) ($row['origin'] ?? 'PROJETO'),
                    'action_type' => (string) ($row['action_type'] ?? 'FALTA'),
                    'quantity' => empty($row['quantity']) ? null : (int) $row['quantity'],
                    'note' => trim((string) ($row['note'] ?? '')) ?: null,
                ];
            })
            ->values();

        if ($rows->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Sem apontamentos',
                'html' => 'Adicione ao menos um apontamento para salvar.',
                'timer' => 2500,
            ]);
            return;
        }

        $allowedActions = ['FALTA', 'ADICIONAR', 'REMOVER', 'ALTERAR'];
        $allowedOrigins = ['LEVANTAMENTO', 'PROJETO', 'AMBOS'];

        foreach ($rows as $index => $row) {
            if ((int) $row['subcategory_id'] <= 0) {
                $this->addError("historyFindingRows.{$index}.subcategory_id", 'Subcategoria inválida.');
            }

            if (!in_array((string) $row['origin'], $allowedOrigins, true)) {
                $this->addError("historyFindingRows.{$index}.origin", 'Origem inválida.');
            }

            if (!in_array((string) $row['action_type'], $allowedActions, true)) {
                $this->addError("historyFindingRows.{$index}.action_type", 'Movimento inválido.');
            }

            if (!is_null($row['quantity']) && (int) $row['quantity'] < 1) {
                $this->addError("historyFindingRows.{$index}.quantity", 'Quantidade inválida.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $firstError = (string) collect($this->getErrorBag()->all())->first();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Não foi possível salvar',
                'html' => $firstError !== '' ? $firstError : 'Existem inconsistências para corrigir.',
                'timer' => 3500,
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($rows) {
                $cycle = ProjectReviewCycle::query()
                    ->where('id', $this->selectedCycle->id)
                    ->lockForUpdate()
                    ->first();

                if (!$cycle) {
                    throw new \RuntimeException('Rodada não encontrada para edição.');
                }

                if (in_array((string) $cycle->decision, ['APPROVED', 'APPROVED_WITH_REMARKS'], true)) {
                    throw new \RuntimeException('A rodada aprovada não pode ser alterada.');
                }

                $cycle->Findings()->delete();

                foreach ($rows as $row) {
                    $payload = [
                        'subcategory_id' => (int) $row['subcategory_id'],
                        'item_id' => empty($row['item_id']) ? null : (int) $row['item_id'],
                        'origin' => (string) $row['origin'],
                        'action_type' => (string) $row['action_type'],
                        'quantity' => empty($row['quantity']) ? null : (int) $row['quantity'],
                        'note' => $row['note'],
                    ];

                    if ($this->hasPointLabelColumn()) {
                        $payload['point_label'] = $this->normalizePointLabel((string) ($row['point_label'] ?? ''));
                    }

                    $cycle->Findings()->create($payload);
                }

                $movementTypes = $rows->pluck('action_type')
                    ->filter(fn ($v) => !empty($v))
                    ->unique()
                    ->values()
                    ->implode(', ');

                Notetimeline::create([
                    'note_id' => $this->selectedProduction->note_id,
                    'service_id' => $this->selectedProduction->service_id,
                    'production_id' => $this->selectedProduction->id,
                    'user_id' => auth()->id(),
                    'info' => 'Estrutura da Análise de Projeto alterada na rodada '
                        . $cycle->round_number
                        . '. Movimentos: '
                        . ($movementTypes !== '' ? $movementTypes : 'SEM MOVIMENTO'),
                    'status' => $this->selectedProduction->status,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Não foi possível salvar',
                'html' => $e->getMessage() ?: 'A rodada foi atualizada por outro usuário. Reabra a tela e tente novamente.',
                'timer' => 3800,
            ]);
            return;
        }

        $this->selectedProduction->refresh();
        $this->selectedProduction->load([
            'Note.Files.Service',
            'User',
            'Company',
            'Service',
            'Files',
            'Analise',
            'ProjectReviewMessages.User',
            'ProjectReviewCycles' => function ($q) {
                $q->with([
                    'Orders',
                    'Findings.Subcategory.Category',
                    'Findings.Item',
                    'DecidedBy',
                    'Messages' => function ($mq) {
                        $mq->with('User')
                            ->orderBy('created_at')
                            ->orderBy('id');
                    },
                ])->latest('round_number');
            },
        ]);
        $this->selectedCycle = collect($this->selectedProduction->ProjectReviewCycles)
            ->firstWhere('id', $this->selectedCycle->id) ?: $this->selectedCycle;

        $this->editingFindings = false;
        $this->historyFindingRows = [];
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Estrutura atualizada com sucesso.',
            'timer' => 2200,
        ]);
    }

    public function getAvailableHistoryPointsProperty()
    {
        return collect($this->selectedCycle?->Findings ?? [])
            ->map(fn ($f) => $this->normalizePointLabel($f->point_label ?? ''))
            ->filter(fn ($label) => $label !== '')
            ->unique()
            ->sort()
            ->values();
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

    public function getFilteredHistoryFindingsProperty()
    {
        $findings = collect($this->selectedCycle?->Findings ?? []);
        if ($this->selectedHistoryPointFilter === '') {
            return $findings;
        }

        return $findings
            ->filter(function ($f) {
                return $this->normalizePointLabel($f->point_label ?? '') === $this->selectedHistoryPointFilter;
            })
            ->values();
    }

    private function normalizePointLabel(?string $value): string
    {
        $label = trim((string) $value);
        if ($label === '') {
            return 'SEM REFERENCIA';
        }

        return mb_substr(mb_strtoupper($label, 'UTF-8'), 0, 120);
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

    public function closeModal(): void
    {
        $this->selectedProduction = null;
        $this->selectedCycle = null;
        $this->selectedHistoryPointFilter = '';
        $this->cancelFindingsEdit();
        $this->resetValidation();
    }

    public function render()
    {
        $rows = $this->selectedProduction ? collect() : $this->rows;

        return view('livewire.project-review.history', [
            'rows' => $rows,
            'companies' => $this->companies,
            'categories' => $this->categories,
            'subcategories' => $this->subcategories,
            'availableSubcategories' => $this->availableSubcategories,
            'availableItems' => $this->availableItems,
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
