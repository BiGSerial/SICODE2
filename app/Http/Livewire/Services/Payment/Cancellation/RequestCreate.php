<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Enum\CancellationRequestStatus;
use App\Enum\CancellationRequestScope;
use App\Models\CancellationCategory;
use App\Models\CancellationRequest;
use App\Models\Note;
use App\Models\Order;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class RequestCreate extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;

    private const BULK_MAX_INPUT = 3000;
    private const BULK_NOTES_RENDER_LIMIT = 600;
    private const BULK_ORDERS_RENDER_LIMIT = 1000;

    public string $service;
    public string $createMode = 'single';

    public string $noteSearch = '';
    public ?Note $note = null;
    public bool $hasOpenNoteFullRequest = false;
    public array $orders = [];
    public string $scope = CancellationRequestScope::NOTE_FULL->value;
    public array $selectedOrders = [];

    public ?string $bulkNotesInput = null;
    public array $bulkCandidates = [];
    public array $bulkOrdersPool = [];
    public array $bulkNotFoundValues = [];
    public array $selectedBulkNoteIds = [];
    public bool $bulkUseAllFilteredNotes = true;
    public string $bulkOrderSearch = '';
    public array $selectedBulkOrderIds = [];
    public bool $bulkSelectAllFilteredOrders = true;
    public bool $bulkProcessed = false;

    public ?int $categoryId = null;
    public ?string $description = null;

    public $files = [];
    public array $tempFiles = [];

    public array $config = [
        'disk' => 'public',
        'base_path' => 'evidences/CANCELLATION_REQUEST',
        'max_size_mb' => 10,
        'allowed_exts' => [
            'jpg','jpeg','png','gif','bmp','svg','tiff','webp',
            'pdf','doc','docx','odt','xls','xlsx','xlsm','ods',
            'dwg','dxf','dws','dwt','dgn','rvt','rfa','skp','txt'
        ],
    ];

    protected $listeners = [
        'resetCancellationForm' => 'resetForm',
        'confirm_cancellation_request_submit' => 'confirmSubmit',
    ];

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updatedFiles(): void
    {
        if ($this->createMode === 'bulk') {
            $this->files = [];
            return;
        }

        $this->validateOnly('files.*');

        foreach ($this->files as $file) {
            $this->tempFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'extension' => strtolower($file->getClientOriginalExtension()),
                'size' => $file->getSize(),
                'file' => $file,
            ];
        }

        $this->files = [];
    }

    public function removeTempFile(int $index): void
    {
        if (isset($this->tempFiles[$index])) {
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles);
        }
    }

    public function setCreateMode(string $mode): void
    {
        if (!in_array($mode, ['single', 'bulk'], true)) {
            return;
        }

        $this->createMode = $mode;

        if ($mode === 'single') {
            $this->resetBulkData();
            return;
        }

        $this->resetNoteData();
        $this->scope = CancellationRequestScope::NOTE_FULL->value;
        $this->files = [];
        $this->tempFiles = [];
    }

    public function updatedScope(): void
    {
        if ($this->createMode !== 'bulk') {
            return;
        }

        $eligibleIds = $this->eligibleBulkNoteIdsForCurrentScope();
        $this->selectedBulkNoteIds = $eligibleIds;
        $this->bulkUseAllFilteredNotes = true;

        $this->selectedBulkOrderIds = [];
        $this->bulkSelectAllFilteredOrders = true;
    }

    public function processBulkNotes(): void
    {
        $rawInput = (string) $this->bulkNotesInput;
        $this->resetBulkData();
        $this->bulkNotesInput = $rawInput;

        $values = collect(preg_split('/[\s,;\n\r]+/', $rawInput))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            $this->addError('bulkNotesInput', 'Informe ao menos uma Nota para pesquisa.');
            return;
        }

        if ($values->count() > self::BULK_MAX_INPUT) {
            $this->addError('bulkNotesInput', 'Limite de itens por pesquisa: ' . self::BULK_MAX_INPUT . '.');
            return;
        }

        $notes = collect();
        foreach ($values->chunk(500) as $chunk) {
            $batch = Note::query()
                ->select(['id', 'note', 'client', 'canceled'])
                ->with(['WorkForm:id,note_id'])
                ->whereIn('note', $chunk->values()->all())
                ->get();

            $notes = $notes->concat($batch);
        }

        $notes = $notes->unique('id')->values();
        $noteIds = $notes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $openNoteFullRequestNoteIds = [];
        if (!empty($noteIds)) {
            $openNoteFullRequestNoteIds = CancellationRequest::query()
                ->whereIn('note_id', $noteIds)
                ->where('scope', CancellationRequestScope::NOTE_FULL->value)
                ->whereIn('status', [
                    CancellationRequestStatus::DRAFT->value,
                    CancellationRequestStatus::SUBMITTED->value,
                    CancellationRequestStatus::ASSIGNED->value,
                    CancellationRequestStatus::PAUSED->value,
                ])
                ->pluck('note_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $ordersByNote = [];
        if (!empty($noteIds)) {
            foreach (array_chunk($noteIds, 500) as $idsChunk) {
                $orders = Order::query()
                    ->select(['id', 'ordem', 'note_id', 'statusUser', 'statusSist', 'canceled'])
                    ->whereIn('note_id', $idsChunk)
                    ->get();

                foreach ($orders as $order) {
                    $status = strtoupper((string) ($order->statusUser ?: $order->statusSist));
                    $isBlockedByStatus = str_starts_with($status, 'ENT') || str_starts_with($status, 'ENC');
                    $isEligible = !$order->canceled && !$isBlockedByStatus;

                    $noteId = (int) $order->note_id;
                    if (!isset($ordersByNote[$noteId])) {
                        $ordersByNote[$noteId] = [
                            'total' => 0,
                            'canceled' => 0,
                            'eligible' => 0,
                            'rows' => [],
                        ];
                    }

                    $ordersByNote[$noteId]['total']++;
                    if ($order->canceled) {
                        $ordersByNote[$noteId]['canceled']++;
                    }

                    if ($isEligible) {
                        $ordersByNote[$noteId]['eligible']++;
                        $ordersByNote[$noteId]['rows'][] = [
                            'id' => (int) $order->id,
                            'ordem' => (string) $order->ordem,
                            'note_id' => $noteId,
                            'status' => (string) ($order->statusUser ?: $order->statusSist ?: '-'),
                        ];
                    }
                }
            }
        }

        $this->bulkCandidates = $notes->map(function ($note) use ($ordersByNote, $openNoteFullRequestNoteIds) {
            $metrics = $ordersByNote[(int) $note->id] ?? ['total' => 0, 'canceled' => 0, 'eligible' => 0, 'rows' => []];
            $hasOpenNoteFullRequest = in_array((int) $note->id, $openNoteFullRequestNoteIds, true);

            $eligibleNoteFull = !$note->canceled
                && !$hasOpenNoteFullRequest
                && (
                    $metrics['total'] === 0
                    || ($metrics['canceled'] === 0 && $metrics['eligible'] > 0)
                );
            $eligibleOrders = !$note->canceled && $metrics['eligible'] > 0;
            $eligibleWorkForm = !$note->canceled && !empty($note->WorkForm);

            return [
                'id' => (int) $note->id,
                'note' => (string) ($note->note ?? '-'),
                'ov' => (string) ($note->ov ?? '-'),
                'client' => (string) ($note->client ?? '-'),
                'note_canceled' => (bool) $note->canceled,
                'has_open_note_full_request' => $hasOpenNoteFullRequest,
                'has_workform' => !empty($note->WorkForm),
                'orders_total' => (int) $metrics['total'],
                'orders_canceled' => (int) $metrics['canceled'],
                'orders_eligible' => (int) $metrics['eligible'],
                'eligible_note_full' => $eligibleNoteFull,
                'eligible_orders' => $eligibleOrders,
                'eligible_workform' => $eligibleWorkForm,
            ];
        })->values()->all();

        $this->bulkOrdersPool = collect($ordersByNote)
            ->flatMap(fn ($metrics, $noteId) => collect($metrics['rows'])->map(function ($row) use ($noteId, $notes) {
                $note = $notes->firstWhere('id', (int) $noteId);
                return [
                    'id' => (int) $row['id'],
                    'ordem' => (string) $row['ordem'],
                    'note_id' => (int) $noteId,
                    'note' => (string) ($note->note ?? '-'),
                    'ov' => (string) ($note->ov ?? '-'),
                    'client' => (string) ($note->client ?? '-'),
                    'status' => (string) $row['status'],
                ];
            }))
            ->values()
            ->all();

        $matchedValues = $notes->flatMap(fn ($note) => [(string) $note->note])
            ->filter(fn ($v) => trim($v) !== '')
            ->unique()
            ->values();

        $this->bulkNotFoundValues = $values
            ->reject(fn ($value) => $matchedValues->contains((string) $value))
            ->values()
            ->all();

        $this->selectedBulkNoteIds = $this->eligibleBulkNoteIdsForCurrentScope();
        $this->bulkUseAllFilteredNotes = true;
        $this->selectedBulkOrderIds = [];
        $this->bulkSelectAllFilteredOrders = true;
        $this->bulkProcessed = true;
    }

    public function findNote(): void
    {
        $this->resetNoteData();

        if (!trim($this->noteSearch)) {
            $this->addError('noteSearch', 'Informe o número da Nota.');
            return;
        }

        $note = Note::where('note', $this->noteSearch)->with('Orders', 'WorkForm')->first();

        if (!$note) {
            $this->addError('noteSearch', 'Nota não encontrada.');
            return;
        }

        $this->note = $note;
        $this->hasOpenNoteFullRequest = $this->noteHasOpenNoteFullRequest((int) $note->id);
        $this->orders = $note->Orders->map(function ($order) {
            return [
                'id' => $order->id,
                'ordem' => $order->ordem,
                'status' => $order->statusUser ?? $order->statusSist,
                'canceled' => (bool) $order->canceled,
            ];
        })->toArray();

        if ($note->canceled || $this->hasOpenNoteFullRequest || $note->Orders->where('canceled', true)->count() > 0) {
            $this->scope = CancellationRequestScope::ORDERS_PARTIAL->value;
        } elseif ($this->scope === CancellationRequestScope::WORK_FORM_ONLY->value && !$note->WorkForm) {
            $this->scope = CancellationRequestScope::NOTE_FULL->value;
        }
    }

    public function submit(): void
    {
        if (!$this->categoryId) {
            $this->addError('categoryId', 'Selecione uma categoria.');
            return;
        }

        $selectedCategory = CancellationCategory::query()
            ->where('active', true)
            ->find($this->categoryId);

        if (!$selectedCategory) {
            $this->addError('categoryId', 'Categoria inválida ou inativa.');
            return;
        }

        $categoryName = $selectedCategory->name;

        if ($this->createMode === 'bulk') {
            $noteIds = $this->resolveBulkTargetNoteIds();
            $ordersCount = $this->scope === CancellationRequestScope::ORDERS_PARTIAL->value
                ? count($this->resolveBulkTargetOrderIds($noteIds))
                : 0;

            $targetLabel = $this->scope === CancellationRequestScope::ORDERS_PARTIAL->value
                ? "{$ordersCount} ordens em {$noteIds->count()} notas"
                : $noteIds->count() . ' notas';

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Confirmar envio em massa',
                'msg' => 'Você está criando solicitações de cancelamento para <strong>' . e($targetLabel) . '</strong> com a categoria <strong>' . e($categoryName) . '</strong>. Deseja continuar?',
                'icon' => 'warning',
                'btnOktxt' => 'Sim, enviar em massa',
                'btnCanceltxt' => 'Não, revisar',
                'action' => 'confirm_cancellation_request_submit',
                'cancel_titulo' => 'Cancelado',
                'cancel_msg' => 'As solicitações não foram enviadas.',
            ]);
            return;
        }

        if ($this->scope === CancellationRequestScope::NOTE_FULL->value
            && $this->note
            && $this->noteHasOpenNoteFullRequest((int) $this->note->id)) {
            $this->addError('scope', 'Já existe solicitação de cancelamento em aberto para a nota inteira.');
            return;
        }

        $noteLabel = $this->note?->note ?: ($this->noteSearch ?: 'não informada');
        $scopeDescription = match ($this->scope) {
            CancellationRequestScope::NOTE_FULL->value => 'da nota',
            CancellationRequestScope::WORK_FORM_ONLY->value => 'somente do WorkForm da',
            default => 'de ordens específicas da',
        };

        $ordersSuffix = '';
        if ($this->scope === CancellationRequestScope::ORDERS_PARTIAL->value) {
            $selectedOrderLabels = collect($this->orders)
                ->filter(fn ($order) => in_array($order['id'], $this->selectedOrders, true))
                ->pluck('ordem')
                ->filter()
                ->values()
                ->all();

            $ordersSuffix = empty($selectedOrderLabels)
                ? ' (OV não selecionada)'
                : ' (OV: ' . e(implode(', ', $selectedOrderLabels)) . ')';
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar envio',
            'msg' => 'Você está solicitando <strong>' . e($categoryName) . '</strong>, o cancelamento ' . $scopeDescription . ' Nota/OV <strong>' . e($noteLabel) . '</strong>' . $ordersSuffix . '. Deseja continuar com a solicitação?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, enviar',
            'btnCanceltxt' => 'Não, revisar',
            'action' => 'confirm_cancellation_request_submit',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'A solicitação não foi enviada.',
        ]);
    }

    public function confirmSubmit(CancellationRequestService $service): void
    {
        $this->authorize('create', CancellationRequest::class);

        if ($this->createMode === 'bulk') {
            $this->confirmSubmitBulk($service);
            return;
        }

        if (!$this->note) {
            $this->addError('noteSearch', 'Carregue uma Nota válida antes de enviar.');
            return;
        }

        $this->validate();

        $category = CancellationCategory::where('active', true)->find($this->categoryId);
        if (!$category) {
            $this->addError('categoryId', 'Categoria inválida ou inativa.');
            return;
        }

        try {
            $attachments = array_map(fn ($item) => $item['file'], $this->tempFiles);

            $service->createRequest(
                $this->note,
                $this->scope,
                $category,
                $this->selectedOrders,
                $attachments,
                Auth::user(),
                $this->description
            );

            $this->dispatchBrowserEvent('swal', [
                'icon' => 'success',
                'title' => 'Solicitação enviada com sucesso!'
            ]);

            $this->resetForm();
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'error',
                'title' => $e->getMessage(),
            ]);
        }
    }

    private function confirmSubmitBulk(CancellationRequestService $service): void
    {
        $this->validate();

        $category = CancellationCategory::where('active', true)->find($this->categoryId);
        if (!$category) {
            $this->addError('categoryId', 'Categoria inválida ou inativa.');
            return;
        }

        $targetNoteIds = $this->resolveBulkTargetNoteIds();
        if ($targetNoteIds->isEmpty()) {
            $this->addError('selectedBulkNoteIds', 'Selecione ao menos uma Nota apta para o escopo escolhido.');
            return;
        }
        $bulkNotesCount = $targetNoteIds->count();
        $bulkDescription = trim((string) $this->description);
        $bulkDescription .= " ** Solicitada em lote de {$bulkNotesCount} notas **";

        $ordersByNote = [];
        if ($this->scope === CancellationRequestScope::ORDERS_PARTIAL->value) {
            $targetOrderIds = $this->resolveBulkTargetOrderIds($targetNoteIds);
            if (empty($targetOrderIds)) {
                $this->addError('selectedBulkOrderIds', 'Selecione ao menos uma ordem válida para cancelamento.');
                return;
            }

            foreach ($targetOrderIds as $id) {
                $order = collect($this->bulkOrdersPool)->firstWhere('id', (int) $id);
                if (!$order) {
                    continue;
                }

                $noteId = (int) $order['note_id'];
                if (!isset($ordersByNote[$noteId])) {
                    $ordersByNote[$noteId] = [];
                }
                $ordersByNote[$noteId][] = (int) $id;
            }
        }

        $processed = 0;
        $errors = 0;

        foreach ($targetNoteIds->chunk(200) as $notesChunk) {
            $notes = Note::query()
                ->with(['Orders', 'WorkForm'])
                ->whereIn('id', $notesChunk->values()->all())
                ->get();

            foreach ($notes as $note) {
                try {
                    if ($this->scope === CancellationRequestScope::ORDERS_PARTIAL->value) {
                        $orderIds = $ordersByNote[(int) $note->id] ?? [];
                        if (empty($orderIds)) {
                            $errors++;
                            continue;
                        }

                        $service->createRequest(
                            $note,
                            $this->scope,
                            $category,
                            $orderIds,
                            [],
                            Auth::user(),
                            $bulkDescription
                        );
                    } else {
                        $service->createRequest(
                            $note,
                            $this->scope,
                            $category,
                            [],
                            [],
                            Auth::user(),
                            $bulkDescription
                        );
                    }

                    $processed++;
                } catch (RuntimeException $e) {
                    $errors++;
                }
            }
        }

        $this->dispatchBrowserEvent('swal', [
            'icon' => $errors ? 'warning' : 'success',
            'title' => "Envio em massa concluído. Sucesso: {$processed} | Erros: {$errors}",
        ]);

        if ($processed > 0) {
            $this->resetForm();
        }
    }

    private function eligibleBulkNoteIdsForCurrentScope(): array
    {
        return collect($this->bulkCandidates)
            ->filter(function ($item) {
                return match ($this->scope) {
                    CancellationRequestScope::NOTE_FULL->value => (bool) ($item['eligible_note_full'] ?? false),
                    CancellationRequestScope::WORK_FORM_ONLY->value => (bool) ($item['eligible_workform'] ?? false),
                    default => (bool) ($item['eligible_orders'] ?? false),
                };
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveBulkTargetNoteIds(): \Illuminate\Support\Collection
    {
        $eligible = collect($this->eligibleBulkNoteIdsForCurrentScope())
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($this->bulkUseAllFilteredNotes) {
            return $eligible;
        }

        return collect($this->selectedBulkNoteIds)
            ->map(fn ($id) => (int) $id)
            ->intersect($eligible)
            ->values();
    }

    private function resolveBulkTargetOrderIds(\Illuminate\Support\Collection $targetNoteIds): array
    {
        $filteredOrders = collect($this->bulkOrdersPool)
            ->whereIn('note_id', $targetNoteIds->values()->all())
            ->filter(function ($row) {
                if (!trim((string) $this->bulkOrderSearch)) {
                    return true;
                }

                $q = mb_strtoupper(trim((string) $this->bulkOrderSearch));
                return str_contains(mb_strtoupper((string) $row['ordem']), $q)
                    || str_contains(mb_strtoupper((string) $row['note']), $q)
                    || str_contains(mb_strtoupper((string) $row['ov']), $q);
            })
            ->values();

        $filteredIds = $filteredOrders->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();

        if ($this->bulkSelectAllFilteredOrders) {
            return $filteredIds->all();
        }

        return collect($this->selectedBulkOrderIds)
            ->map(fn ($id) => (int) $id)
            ->intersect($filteredIds)
            ->values()
            ->all();
    }

    protected function rules(): array
    {
        $maxKb = $this->config['max_size_mb'] * 1024;
        $mimes = implode(',', $this->config['allowed_exts']);

        $rules = [
            'createMode' => 'required|in:single,bulk',
            'categoryId' => 'required|integer',
            'description' => 'required|string|max:2000',
        ];

        if ($this->createMode === 'single') {
            $rules['noteSearch'] = 'required|string';
            $rules['scope'] = 'required|in:' . implode(',', CancellationRequestScope::values());
            $rules['selectedOrders'] = 'array';
            $rules['selectedOrders.*'] = 'integer';
            $rules['files.*'] = "nullable|file|mimes:{$mimes}|max:{$maxKb}";

            if ($this->scope === CancellationRequestScope::ORDERS_PARTIAL->value) {
                $rules['selectedOrders'] = 'required|array|min:1';
            }

            return $rules;
        }

        $rules['bulkNotesInput'] = 'required|string';
        $rules['scope'] = 'required|in:' . implode(',', [
            CancellationRequestScope::NOTE_FULL->value,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            CancellationRequestScope::WORK_FORM_ONLY->value,
        ]);
        $rules['selectedBulkNoteIds'] = 'array';
        $rules['selectedBulkNoteIds.*'] = 'integer';
        $rules['selectedBulkOrderIds'] = 'array';
        $rules['selectedBulkOrderIds.*'] = 'integer';

        return $rules;
    }

    private function resetForm(): void
    {
        $this->createMode = 'single';
        $this->noteSearch = '';
        $this->note = null;
        $this->orders = [];
        $this->scope = CancellationRequestScope::NOTE_FULL->value;
        $this->selectedOrders = [];
        $this->resetBulkData();
        $this->categoryId = null;
        $this->description = null;
        $this->files = [];
        $this->tempFiles = [];
    }

    private function resetNoteData(): void
    {
        $this->note = null;
        $this->hasOpenNoteFullRequest = false;
        $this->orders = [];
        $this->selectedOrders = [];
    }

    private function resetBulkData(): void
    {
        $this->bulkNotesInput = null;
        $this->bulkCandidates = [];
        $this->bulkOrdersPool = [];
        $this->bulkNotFoundValues = [];
        $this->selectedBulkNoteIds = [];
        $this->bulkUseAllFilteredNotes = true;
        $this->bulkOrderSearch = '';
        $this->selectedBulkOrderIds = [];
        $this->bulkSelectAllFilteredOrders = true;
        $this->bulkProcessed = false;
    }

    private function noteHasOpenNoteFullRequest(int $noteId): bool
    {
        if ($noteId <= 0) {
            return false;
        }

        return CancellationRequest::query()
            ->where('note_id', $noteId)
            ->where('scope', CancellationRequestScope::NOTE_FULL->value)
            ->whereIn('status', [
                CancellationRequestStatus::DRAFT->value,
                CancellationRequestStatus::SUBMITTED->value,
                CancellationRequestStatus::ASSIGNED->value,
                CancellationRequestStatus::PAUSED->value,
            ])
            ->exists();
    }

    public function render()
    {
        $categories = CancellationCategory::where('active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $noteCanceled = $this->note?->canceled ?? false;
        $hasCanceledOrders = $this->note && $this->note->Orders->where('canceled', true)->count() > 0;
        $hasWorkForm = (bool) $this->note?->WorkForm;

        $bulkEligibleNoteIds = collect($this->eligibleBulkNoteIdsForCurrentScope());
        $bulkTotalNotes = count($this->bulkCandidates);
        $bulkEligibleNotes = $bulkEligibleNoteIds->count();
        $bulkSelectedNotes = $this->bulkUseAllFilteredNotes
            ? $bulkEligibleNotes
            : collect($this->selectedBulkNoteIds)->intersect($bulkEligibleNoteIds)->count();

        $targetNoteIds = $this->resolveBulkTargetNoteIds();
        $bulkFilteredOrders = collect($this->bulkOrdersPool)
            ->whereIn('note_id', $targetNoteIds->values()->all())
            ->filter(function ($row) {
                if (!trim((string) $this->bulkOrderSearch)) {
                    return true;
                }

                $q = mb_strtoupper(trim((string) $this->bulkOrderSearch));
                return str_contains(mb_strtoupper((string) $row['ordem']), $q)
                    || str_contains(mb_strtoupper((string) $row['note']), $q)
                    || str_contains(mb_strtoupper((string) $row['ov']), $q);
            })
            ->values();

        $bulkSelectedOrders = $this->bulkSelectAllFilteredOrders
            ? $bulkFilteredOrders->count()
            : collect($this->selectedBulkOrderIds)->intersect($bulkFilteredOrders->pluck('id'))->count();

        $bulkHasValidTargets = $this->scope === CancellationRequestScope::ORDERS_PARTIAL->value
            ? $bulkSelectedOrders > 0
            : $bulkSelectedNotes > 0;

        return view('livewire.services.payment.cancellation.request-create', [
            'categories' => $categories,
            'noteCanceled' => $noteCanceled,
            'hasOpenNoteFullRequest' => $this->hasOpenNoteFullRequest,
            'hasCanceledOrders' => $hasCanceledOrders,
            'hasWorkForm' => $hasWorkForm,
            'bulkTotalNotes' => $bulkTotalNotes,
            'bulkEligibleNotes' => $bulkEligibleNotes,
            'bulkSelectedNotes' => $bulkSelectedNotes,
            'bulkVisibleNotes' => collect($this->bulkCandidates)->take(self::BULK_NOTES_RENDER_LIMIT)->values(),
            'bulkNotesRenderLimit' => self::BULK_NOTES_RENDER_LIMIT,
            'bulkFilteredOrders' => $bulkFilteredOrders->take(self::BULK_ORDERS_RENDER_LIMIT)->values(),
            'bulkOrdersRenderLimit' => self::BULK_ORDERS_RENDER_LIMIT,
            'bulkFilteredOrdersTotal' => $bulkFilteredOrders->count(),
            'bulkSelectedOrders' => $bulkSelectedOrders,
            'bulkHasValidTargets' => $bulkHasValidTargets,
        ]);
    }
}
