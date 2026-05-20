<?php

namespace App\Http\Livewire\Partner;

use App\Enum\AdsRequestStatus;
use App\Jobs\Ads\ExportAdsRequestsHistoryJob;
use App\Models\AdsRequest;
use App\Models\Note;
use App\Models\SicodeSql\AdsRequest as SqlAdsRequest;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AdsRequests extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['confirm_ads_requests_process' => 'confirmProcessRequests'];

    public $notesInput = '';
    public $previewItems = [];
    public $selectedPreviewItems = [];
    public $activeSearch = '';
    public $activePerPage = 25;

    public $historyStart;
    public $historyEnd;
    public $historyPerPage = 25;
    public $historySearch = '';
    public $historyCompanyId;
    public bool $sqlSyncEnabled = true;
    public bool $isProcessingRequests = false;

    public function mount()
    {
        $this->historyStart = now()->subDays(30)->toDateString();
        $this->historyEnd = now()->toDateString();
        $this->sqlSyncEnabled = !SystemSetting::getBool('ads_auto_test_mode', false);
    }

    public function updatedHistoryStart()
    {
        $this->resetPage('historyPage');
    }

    public function updatedHistoryEnd()
    {
        $this->resetPage('historyPage');
    }

    public function updatedHistoryPerPage()
    {
        $this->resetPage('historyPage');
    }

    public function updatedHistoryCompanyId()
    {
        $this->resetPage('historyPage');
    }

    public function updatedActiveSearch()
    {
        $this->resetPage('activePage');
    }

    public function updatedActivePerPage()
    {
        $this->resetPage('activePage');
    }

    public function updatedHistorySearch()
    {
        $this->resetPage('historyPage');
    }

    public function analyzeNotes()
    {
        $noteNumbers = $this->parseNotesInput();

        if (!$noteNumbers) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Informe ao menos uma nota.',
                'timer' => 3000,
            ]);

            return;
        }

        $companyId = $this->resolveCompanyId();
        if (!$companyId) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Usuário sem empresa vinculada.',
                'timer' => 4000,
            ]);

            return;
        }

        $items = [];

        foreach ($noteNumbers as $noteNumber) {
            $note = Note::query()
                ->where('note', $noteNumber)
                ->first();

            if (!$note) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => null,
                    'status_label' => 'Não existe no SICODE',
                    'status_class' => 'text-bg-danger',
                    'message' => 'Não existe registro para esta nota no SICODE.',
                    'can_process' => false,
                    'previous_request_id' => null,
                    'previous_status' => null,
                    'last_url' => null,
                    'last_url_age' => null,
                ];
                continue;
            }

            $hasOrders = $note->Orders()->exists();
            if (!$hasOrders) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => $note->id,
                    'status_label' => 'Sem ordem',
                    'status_class' => 'text-bg-danger',
                    'message' => 'Não há ORDERS para a nota selecionada.',
                    'can_process' => false,
                    'previous_request_id' => null,
                    'previous_status' => null,
                    'last_url' => null,
                    'last_url_age' => null,
                ];
                continue;
            }

            $hasLibOrder = $note->Orders()
                ->where('statusSist', 'like', 'LIB%')
                ->exists();

            if (!$hasLibOrder) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => $note->id,
                    'status_label' => 'Sem ordem LIB',
                    'status_class' => 'text-bg-warning',
                    'message' => 'Não há ORDEM liberada para processar.',
                    'can_process' => false,
                    'previous_request_id' => null,
                    'previous_status' => null,
                    'last_url' => null,
                    'last_url_age' => null,
                ];
                continue;
            }

            $previousRequest = $this->getActiveRequestFor($note->id, $companyId, (string) auth()->id());
            $latestRequestWithUrl = $this->getLatestRequestWithUrlFor($note->id);

            if ($latestRequestWithUrl) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => $note->id,
                    'status_label' => 'Aproveitar link',
                    'status_class' => 'text-bg-info',
                    'message' => 'Já existe ADS disponível para esta obra. Ao processar, a solicitação será finalizada localmente, sem novo envio para a fila do SQL.',
                    'can_process' => true,
                    'previous_request_id' => $previousRequest?->id,
                    'previous_status' => $previousRequest?->status?->label(),
                    'will_cancel' => false,
                    'last_url' => $latestRequestWithUrl->url,
                    'last_url_age' => $this->formatElapsed($latestRequestWithUrl->updated_at ?? $latestRequestWithUrl->completed_at ?? $latestRequestWithUrl->created_at),
                ];
                continue;
            }

            if ($previousRequest) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => $note->id,
                    'status_label' => 'Reagendar',
                    'status_class' => 'text-bg-warning',
                    'message' => 'Há uma solicitação em andamento. Ela será cancelada e reagendada.',
                    'can_process' => true,
                    'previous_request_id' => $previousRequest->id,
                    'previous_status' => $previousRequest->status?->label(),
                    'will_cancel' => true,
                    'last_url' => $latestRequestWithUrl?->url,
                    'last_url_age' => $this->formatElapsed($latestRequestWithUrl?->updated_at ?? $latestRequestWithUrl?->completed_at ?? $latestRequestWithUrl?->created_at),
                ];
                continue;
            }

            $items[$noteNumber] = [
                'note_number' => $noteNumber,
                'note_id' => $note->id,
                'status_label' => 'Apto',
                'status_class' => 'text-bg-success',
                'message' => 'Pronto para solicitar.',
                'can_process' => true,
                'previous_request_id' => null,
                'previous_status' => null,
                'will_cancel' => false,
                'last_url' => $latestRequestWithUrl?->url,
                'last_url_age' => $this->formatElapsed($latestRequestWithUrl?->updated_at ?? $latestRequestWithUrl?->completed_at ?? $latestRequestWithUrl?->created_at),
            ];
        }

        $this->previewItems = $items;
        $this->selectedPreviewItems = [];
    }

    public function removePreview(string $noteNumber)
    {
        if (isset($this->previewItems[$noteNumber])) {
            unset($this->previewItems[$noteNumber]);
        }

        $this->selectedPreviewItems = array_values(array_filter(
            $this->selectedPreviewItems,
            fn ($selected) => (string) $selected !== (string) $noteNumber
        ));
    }

    public function removeSelectedPreview()
    {
        if (!$this->selectedPreviewItems) {
            return;
        }

        foreach ($this->selectedPreviewItems as $noteNumber) {
            if (isset($this->previewItems[$noteNumber])) {
                unset($this->previewItems[$noteNumber]);
            }
        }

        $this->selectedPreviewItems = [];
    }

    public function clearPreview()
    {
        $this->previewItems = [];
        $this->selectedPreviewItems = [];
    }

    public function removeAllPreview()
    {
        $this->previewItems = [];
        $this->selectedPreviewItems = [];
    }

    public function processRequests()
    {
        if ($this->isProcessingRequests) {
            return;
        }

        $cancelNotes = $this->getCancelablePreviewNotes();
        if ($cancelNotes) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Confirmar reagendamento',
                'msg' => $this->buildCancelNotesMessage($cancelNotes),
                'icon' => 'warning',
                'btnOktxt' => 'Sim, cancelar e reenviar',
                'btnCanceltxt' => 'Não, cancelar',
                'action' => 'confirm_ads_requests_process',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma solicitação foi alterada.',
            ]);

            return;
        }

        $this->isProcessingRequests = true;
        try {
            $this->processRequestsInternal();
        } finally {
            $this->isProcessingRequests = false;
        }
    }

    public function confirmProcessRequests()
    {
        if ($this->isProcessingRequests) {
            return;
        }

        $this->isProcessingRequests = true;
        try {
            $this->processRequestsInternal(true);
        } finally {
            $this->isProcessingRequests = false;
        }
    }

    protected function processRequestsInternal(bool $force = false): void
    {
        if (!$this->previewItems) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma nota válida para processar.',
                'timer' => 3000,
            ]);
            return;
        }

        $processableCount = collect($this->previewItems)
            ->where('can_process', true)
            ->count();

        if ($processableCount === 0) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Não foi possível concluir a solicitação.',
                'html' => $this->buildBlockedPreviewHtml(),
            ]);
            return;
        }

        if (!$force && $this->getCancelablePreviewNotes()) {
            return;
        }

        $companyId = $this->resolveCompanyId();
        if (!$companyId) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Usuário sem empresa vinculada.',
                'timer' => 4000,
            ]);
            return;
        }

        $batchId = (string) Str::uuid();
        $created = 0;
        $skippedDuplicates = 0;
        $mirrorFailures = [];
        $toMirror = [];

        DB::transaction(function () use ($companyId, $batchId, &$created, &$skippedDuplicates, &$toMirror) {
            foreach ($this->previewItems as $item) {
                if (!$item['can_process']) {
                    continue;
                }

                if (!empty($item['last_url'])) {
                    $hasOwnRequest = AdsRequest::query()
                        ->where('note_id', $item['note_id'])
                        ->where('company_id', $companyId)
                        ->where('requested_by', auth()->id())
                        ->exists();

                    if ($hasOwnRequest) {
                        $skippedDuplicates++;
                        continue;
                    }

                    $version = (int) AdsRequest::query()
                        ->where('note_id', $item['note_id'])
                        ->max('version');

                    AdsRequest::query()->create([
                        'requested_by' => auth()->id(),
                        'company_id' => $companyId,
                        'note_id' => $item['note_id'],
                        'batch_id' => $batchId,
                        'partner' => true,
                        'completed' => true,
                        'status' => AdsRequestStatus::DONE,
                        'version' => $version + 1,
                        'description' => 'ADS já disponível. Solicitação finalizada automaticamente com link já existente.',
                        'url' => $item['last_url'],
                        'completed_at' => now(),
                    ]);

                    $created++;
                    continue;
                }

                $activeRequests = AdsRequest::query()
                    ->where('note_id', $item['note_id'])
                    ->where('company_id', $companyId)
                    ->where('requested_by', auth()->id())
                    ->whereIn('status', $this->activeStatuses())
                    ->lockForUpdate()
                    ->orderByDesc('created_at')
                    ->get();

                $previousRequest = $activeRequests->first();
                $shouldReschedule = !empty($item['will_cancel']);

                if ($previousRequest && !$shouldReschedule) {
                    $skippedDuplicates++;
                    continue;
                }

                $version = (int) AdsRequest::query()
                    ->where('note_id', $item['note_id'])
                    ->max('version');

                if ($previousRequest) {
                    foreach ($activeRequests as $activeRequest) {
                        $activeRequest->update([
                            'status' => AdsRequestStatus::CANCELED,
                            'canceled_at' => now(),
                            'superseded_by_id' => null,
                        ]);

                        if ($this->sqlSyncEnabled) {
                            $this->syncCanceledToSqlServer($activeRequest);
                        }
                    }
                }

                $request = AdsRequest::query()->create([
                    'requested_by' => auth()->id(),
                    'company_id' => $companyId,
                    'note_id' => $item['note_id'],
                    'batch_id' => $batchId,
                    'partner' => true,
                    'completed' => false,
                    'status' => AdsRequestStatus::QUEUED,
                    'version' => $version + 1,
                ]);

                if ($previousRequest) {
                    $previousRequest->update([
                        'superseded_by_id' => $request->id,
                    ]);
                }

                $toMirror[] = [
                    'request' => $request,
                    'note_number' => $item['note_number'],
                ];

                $created++;
            }
        });

        if ($this->sqlSyncEnabled) {
            foreach ($toMirror as $payload) {
                if (!$this->mirrorToSqlServer($payload['request'], $payload['note_number'])) {
                    $mirrorFailures[] = $payload['note_number'];
                }
            }
        }

        $this->previewItems = [];
        $this->selectedPreviewItems = [];

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => $created . ' solicitação(ões) criada(s).' . ($skippedDuplicates ? " {$skippedDuplicates} duplicada(s) ignorada(s)." : ''),
            'timer' => 3500,
        ]);

        if ($mirrorFailures) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Falha ao enviar algumas notas ao SQL Server.',
                'timer' => 4000,
            ]);
        }
    }

    protected function mirrorToSqlServer(AdsRequest $request, string $noteNumber): bool
    {
        try {
            $user = $request->requestedBy()->first();
            $company = $request->company()->first();
            $status = $request->status instanceof AdsRequestStatus
                ? $request->status->value
                : AdsRequestStatus::QUEUED->value;
            $payload = [
                'batch_id' => $request->batch_id,
                'note' => $noteNumber,
                'company' => $company?->name,
                'status' => $status,
                'attempts' => $request->attempts ?? 0,
                'partner' => $request->partner ? 1 : 0,
                'register' => $user?->Registration,
                'user' => $user?->name,
                'email' => $user?->email,
                'description' => $request->description,
                'completed_at' => $request->completed_at,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
            ];
            $sqlTable = DB::connection('sqlsrv2')->table('sicode.dbo.ads_requests');

            if ($sqlTable->where('sicode_id', $request->id)->exists()) {
                $sqlTable->where('sicode_id', $request->id)->update($payload);
            } else {
                $sqlTable->insert(array_merge(['sicode_id' => $request->id], $payload));
            }

            return $this->syncRequestFromSqlServer($request);
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function getActiveRequestFor(int $noteId, string $companyId, string $requestedBy): ?AdsRequest
    {
        return AdsRequest::query()
            ->where('note_id', $noteId)
            ->where('company_id', $companyId)
            ->where('requested_by', $requestedBy)
            ->whereIn('status', $this->activeStatuses())
            ->latest('created_at')
            ->first();
    }

    protected function activeStatuses(): array
    {
        return collect(AdsRequestStatus::cases())
            ->map(fn (AdsRequestStatus $status) => $status->value)
            ->reject(fn (string $status) => in_array($status, [
                AdsRequestStatus::DONE->value,
                AdsRequestStatus::CANCELED->value,
                AdsRequestStatus::FAILED->value,
            ], true))
            ->values()
            ->all();
    }

    protected function getLatestRequestWithUrlFor(int $noteId): ?AdsRequest
    {
        return AdsRequest::query()
            ->where('note_id', $noteId)
            ->whereNotNull('url')
            ->whereRaw("NULLIF(LTRIM(RTRIM(url)), '') IS NOT NULL")
            ->latest('created_at')
            ->first();
    }

    protected function formatElapsed($value): ?string
    {
        if (!$value) {
            return null;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return $date->diffForHumans(now(), [
            'parts' => 2,
            'short' => true,
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
        ]);
    }

    protected function getCancelablePreviewNotes(): array
    {
        return collect($this->previewItems)
            ->filter(fn ($item) => !empty($item['can_process']) && !empty($item['will_cancel']))
            ->pluck('note_number')
            ->values()
            ->all();
    }

    protected function buildCancelNotesMessage(array $notes): string
    {
        $list = array_slice($notes, 0, 8);
        $extra = count($notes) > 8 ? ' e mais ' . (count($notes) - 8) . '...' : '';

        return 'Existem solicitações em andamento que serão canceladas e reagendadas para as notas: <strong>' .
            implode(', ', $list) . $extra . '</strong>. Deseja continuar?';
    }

    protected function buildBlockedPreviewHtml(): string
    {
        $blocked = collect($this->previewItems)
            ->filter(fn ($item) => empty($item['can_process']))
            ->values();

        if ($blocked->isEmpty()) {
            return '<p class="mb-0">Nenhuma nota apta para processamento.</p>';
        }

        $items = $blocked
            ->take(12)
            ->map(function ($item) {
                $note = e((string) ($item['note_number'] ?? '-'));
                $message = e((string) ($item['message'] ?? 'Sem detalhe.'));
                return "<li><strong>{$note}:</strong> {$message}</li>";
            })
            ->implode('');

        $extra = $blocked->count() > 12
            ? '<p class="mt-2 mb-0 text-muted">E mais '.($blocked->count() - 12).' nota(s) bloqueada(s).</p>'
            : '';

        return "<div class='text-start'><p class='mb-2'>Nenhuma nota da lista está apta. Motivos:</p><ul class='mb-0'>{$items}</ul>{$extra}</div>";
    }

    protected function syncCanceledToSqlServer(AdsRequest $request): bool
    {
        try {
            $affected = DB::connection('sqlsrv2')
                ->table('sicode.dbo.ads_requests')
                ->where('sicode_id', $request->id)
                ->update([
                    'status' => AdsRequestStatus::CANCELED->value,
                    'updated_at' => now(),
                    'completed_at' => $request->canceled_at ?? now(),
                ]);

            if ((int) $affected === 0) {
                $noteNumber = $request->note?->note ?? (string) $request->note_id;
                return $this->mirrorToSqlServer($request, $noteNumber);
            }

            return $this->syncRequestFromSqlServer($request);
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    protected function resolveCompanyId(): ?string
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        if ($user->company_id) {
            return $user->company_id;
        }

        if ($user->Companies && $user->Companies->isNotEmpty()) {
            return $user->Companies->first()->id;
        }

        return null;
    }

    protected function parseNotesInput(): array
    {
        $raw = preg_split('/[\s,;]+/', trim((string) $this->notesInput));

        return collect($raw)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function parseHistorySearchTerms(): array
    {
        $raw = preg_split('/[\s,;]+/', trim((string) $this->historySearch));

        return collect($raw)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function parseActiveSearchTerms(): array
    {
        $raw = preg_split('/[\s,;]+/', trim((string) $this->activeSearch));

        return collect($raw)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function clearHistoryFilters()
    {
        $this->historyStart = null;
        $this->historyEnd = null;
        $this->historySearch = '';
        $this->historyCompanyId = null;
        $this->resetPage('historyPage');
    }

    public function exportHistory()
    {
        $user = auth()->user();

        ExportAdsRequestsHistoryJob::dispatch([
            'start' => $this->historyStart,
            'end' => $this->historyEnd,
            'search' => $this->historySearch,
            'company_id' => $user && $user->superadm ? $this->historyCompanyId : null,
        ], (string) auth()->id(), 'partner');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportacao solicitada. Aguarde a notificacao.',
            'timer' => 3000,
        ]);
    }

    public function getHistoryCompanyOptionsProperty()
    {
        $user = auth()->user();

        if (!$user || !$user->superadm) {
            return collect();
        }

        return \App\Models\Company::query()->orderBy('name')->get();
    }

    public function getActiveRequestsProperty()
    {
        $query = AdsRequest::query()
            ->with(['note', 'company'])
            ->when(!auth()->user()?->superadm, function ($q) {
                $q->where('requested_by', auth()->id());
            })
            ->whereNotIn('status', [
                AdsRequestStatus::DONE->value,
                AdsRequestStatus::CANCELED->value,
                AdsRequestStatus::FAILED->value,
            ])
            ->orderByDesc('created_at');

        if ($this->activeSearch) {
            $terms = $this->parseActiveSearchTerms();
            if ($terms) {
                $query->whereHas('note', function ($q) use ($terms) {
                    if (count($terms) === 1) {
                        $q->where('note', 'like', '%' . $terms[0] . '%');
                        return;
                    }

                    $q->whereIn('note', $terms);
                });
            }
        }

        return $query->paginate($this->activePerPage, ['*'], 'activePage');
    }

    public function syncAllRequests()
    {
        if (!$this->sqlSyncEnabled) {
            $this->notifyTestMode();
            return;
        }

        $requests = $this->activeRequests;

        if ($requests->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'info',
                'title' => 'Nenhuma solicitacao em andamento.',
                'timer' => 3000,
            ]);
            return;
        }

        $sqlRows = $this->loadSqlStatusBySicodeIds($requests->pluck('id'));

        $updated = 0;
        $resent = 0;
        $failed = 0;

        foreach ($requests as $request) {
            $sqlRow = $sqlRows->get($request->id);

            if (!$sqlRow) {
                $noteNumber = $request->note?->note ?? (string) $request->note_id;

                if ($this->mirrorToSqlServer($request, $noteNumber)) {
                    $resent++;
                } else {
                    $failed++;
                }

                continue;
            }

            if (
                $request->status === AdsRequestStatus::CANCELED
                && $this->normalizeSqlStatus((string) $sqlRow->status) !== AdsRequestStatus::CANCELED->value
            ) {
                if ($this->syncCanceledToSqlServer($request)) {
                    $updated++;
                } else {
                    $failed++;
                }
                continue;
            }

            if ($this->applySqlRowToLocalRequest($request, $sqlRow)) {
                $updated++;
            }
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => $failed > 0 ? 'warning' : 'success',
            'title' => 'Sincronizacao concluida.',
            'text' => "Atualizadas: {$updated} | Reenviadas: {$resent} | Falhas: {$failed}",
            'timer' => 4000,
        ]);
    }

    public function syncRequest(int $id)
    {
        if (!$this->sqlSyncEnabled) {
            $this->notifyTestMode();
            return;
        }

        $request = AdsRequest::find($id);

        if (!$request) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Solicitacao nao encontrada.',
                'timer' => 3000,
            ]);
            return;
        }

        if (!$this->syncRequestFromSqlServer($request)) {
            $noteNumber = $request->note?->note ?? (string) $request->note_id;

            if ($this->mirrorToSqlServer($request, $noteNumber)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'success',
                    'title' => 'Registro reenviado ao SQL Server.',
                    'timer' => 3000,
                ]);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'error',
                    'title' => 'Falha ao reenviar ao SQL Server.',
                    'timer' => 3000,
                ]);
            }

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Solicitacao sincronizada.',
            'timer' => 3000,
        ]);
    }

    public function getHistoryRequestsProperty()
    {
        $query = AdsRequest::query()
            ->with(['note', 'company'])
            ->when(!auth()->user()?->superadm, function ($q) {
                $q->where('requested_by', auth()->id());
            })
            ->whereIn('status', [
                AdsRequestStatus::DONE->value,
                AdsRequestStatus::FAILED->value,
                AdsRequestStatus::CANCELED->value,
            ]);

        if ($this->historySearch) {
            $terms = $this->parseHistorySearchTerms();
            if ($terms) {
                $query->whereHas('note', function ($q) use ($terms) {
                    if (count($terms) === 1) {
                        $q->where('note', 'like', '%' . $terms[0] . '%');
                        return;
                    }

                    $q->whereIn('note', $terms);
                });
            }
        }

        if ($this->historyCompanyId && auth()->user()?->superadm) {
            $query->where('company_id', $this->historyCompanyId);
        }

        if ($this->historyStart) {
            $query->whereDate('created_at', '>=', $this->historyStart);
        }

        if ($this->historyEnd) {
            $query->whereDate('created_at', '<=', $this->historyEnd);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($this->historyPerPage, ['*'], 'historyPage');
    }

    public function render()
    {
        $activeRequests = $this->activeRequests;
        $sqlStatusBySicodeId = $this->sqlSyncEnabled
            ? $this->loadSqlStatusBySicodeIds($activeRequests->pluck('id'))
            : collect();

        return view('livewire.partner.ads-requests', [
            'activeRequests' => $activeRequests,
            'sqlStatusBySicodeId' => $sqlStatusBySicodeId,
            'historyRequests' => $this->historyRequests,
            'historyCompanyOptions' => $this->historyCompanyOptions,
            'sqlSyncEnabled' => $this->sqlSyncEnabled,
        ]);
    }

    protected function notifyTestMode(): void
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'info',
            'title' => 'Modo teste sem envio para SQL Server está habilitado.',
            'timer' => 3200,
        ]);
    }

    protected function loadSqlStatusBySicodeIds($ids)
    {
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = collect();
        foreach ($ids->chunk(1800) as $chunk) {
            $rows = $rows->merge(
                SqlAdsRequest::query()
                    ->whereIn('sicode_id', $chunk->all())
                    ->get(['id', 'sicode_id', 'status', 'attempts', 'description', 'url', 'completed_at', 'updated_at'])
            );
        }

        return $rows->keyBy('sicode_id');
    }

    protected function syncRequestFromSqlServer(AdsRequest $request): bool
    {
        $sqlRow = SqlAdsRequest::query()
            ->where('sicode_id', $request->id)
            ->latest('updated_at')
            ->first();

        if (!$sqlRow && $request->sqlserver_id) {
            $sqlRow = SqlAdsRequest::query()->find($request->sqlserver_id);
        }

        if (!$sqlRow) {
            return false;
        }

        $this->applySqlRowToLocalRequest($request, $sqlRow);

        return true;
    }

    protected function applySqlRowToLocalRequest(AdsRequest $request, $sqlRow): bool
    {
        $sqlStatus = $this->normalizeSqlStatus($sqlRow->status)
            ?? ($request->status instanceof AdsRequestStatus ? $request->status->value : AdsRequestStatus::QUEUED->value);

        $request->fill([
            'status' => $sqlStatus,
            'attempts' => (int) ($sqlRow->attempts ?? 0),
            'description' => $sqlRow->description,
            'url' => $sqlRow->url,
            'completed_at' => $sqlRow->completed_at,
            'sqlserver_id' => $sqlRow->id,
            'completed' => $sqlStatus === AdsRequestStatus::DONE->value,
            'updated_at' => $sqlRow->updated_at,
        ]);

        if (!$request->isDirty()) {
            return false;
        }

        $request->timestamps = false;
        $request->save();

        return true;
    }

    protected function normalizeSqlStatus(?string $status): ?string
    {
        $normalized = mb_strtoupper(trim((string) $status));
        if ($normalized === '') {
            return null;
        }

        return AdsRequestStatus::tryFrom($normalized)?->value;
    }
}
