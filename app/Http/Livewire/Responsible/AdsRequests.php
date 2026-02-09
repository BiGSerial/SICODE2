<?php

namespace App\Http\Livewire\Responsible;

use App\Enum\AdsRequestStatus;
use App\Jobs\Ads\ExportAdsRequestsHistoryJob;
use App\Models\AdsRequest;
use App\Models\Company;
use App\Models\Note;
use App\Models\SicodeSql\AdsRequest as SqlAdsRequest;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AdsRequests extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['confirm_ads_requests_process' => 'confirmProcessRequests'];

    public $notesInput = '';
    public $previewItems = [];
    public $activeSearch = '';
    public $activePerPage = 25;
    public $companyId;

    public $historyStart;
    public $historyEnd;
    public $historyPerPage = 25;
    public $historySearch = '';
    public $historyCompanyId;
    public bool $sqlSyncEnabled = true;

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

    public function updatedHistorySearch()
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

        if (!$this->companyId) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione uma empresa antes de analisar.',
                'timer' => 3000,
            ]);

            return;
        }

        $companyId = $this->companyId;
        $items = [];

        foreach ($noteNumbers as $noteNumber) {
            $note = Note::query()
                ->where('note', $noteNumber)
                ->first();

            if (!$note) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => null,
                    'status_label' => 'Nao existe no SICODE',
                    'status_class' => 'text-bg-danger',
                    'message' => 'Nao existe registro para esta nota no SICODE.',
                    'can_process' => false,
                    'previous_request_id' => null,
                    'previous_status' => null,
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
                    'message' => 'Nao consta ORDERS para a nota selecionada.',
                    'can_process' => false,
                    'previous_request_id' => null,
                    'previous_status' => null,
                ];
                continue;
            }

            $hasActiveOrders = $note->Orders()
                ->where(function ($query) {
                    $query->where('statusSist', 'not like', 'ENT%')
                        ->where('statusSist', 'not like', 'ENC%');
                })
                ->exists();

            if (!$hasActiveOrders) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => $note->id,
                    'status_label' => 'Sem ordem ativa',
                    'status_class' => 'text-bg-warning',
                    'message' => 'Todas as ORDERS estao em ENT ou ENC.',
                    'can_process' => false,
                    'previous_request_id' => null,
                    'previous_status' => null,
                ];
                continue;
            }

            $previousRequest = $this->getActiveRequestFor($note->id, $companyId);

            if ($previousRequest) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => $note->id,
                    'status_label' => 'Reagendar',
                    'status_class' => 'text-bg-warning',
                    'message' => 'Solicitacao em andamento sera cancelada e reagendada.',
                    'can_process' => true,
                    'previous_request_id' => $previousRequest->id,
                    'previous_status' => $previousRequest->status?->label(),
                    'will_cancel' => true,
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
            ];
        }

        $this->previewItems = $items;
    }

    public function removePreview(string $noteNumber)
    {
        if (isset($this->previewItems[$noteNumber])) {
            unset($this->previewItems[$noteNumber]);
        }
    }

    public function removeAllPreview()
    {
        $this->previewItems = [];
    }

    public function processRequests()
    {
        $cancelNotes = $this->getCancelablePreviewNotes();
        if ($cancelNotes) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Confirmar reagendamento',
                'msg' => $this->buildCancelNotesMessage($cancelNotes),
                'icon' => 'warning',
                'btnOktxt' => 'Sim, cancelar e reenviar',
                'btnCanceltxt' => 'Nao, cancelar',
                'action' => 'confirm_ads_requests_process',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma solicitacao foi alterada.',
            ]);

            return;
        }

        $this->processRequestsInternal();
    }

    public function confirmProcessRequests()
    {
        $this->processRequestsInternal(true);
    }

    protected function processRequestsInternal(bool $force = false): void
    {
        if (!$this->previewItems) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma nota valida para processar.',
                'timer' => 3000,
            ]);
            return;
        }

        if (!$force && $this->getCancelablePreviewNotes()) {
            return;
        }

        if (!$this->companyId) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione uma empresa antes de processar.',
                'timer' => 3000,
            ]);
            return;
        }

        $companyId = $this->companyId;
        $batchId = (string) Str::uuid();
        $created = 0;
        $mirrorFailures = [];
        $toMirror = [];

        DB::transaction(function () use ($companyId, $batchId, &$created, &$toMirror) {
            foreach ($this->previewItems as $item) {
                if (!$item['can_process']) {
                    continue;
                }

                $version = (int) AdsRequest::query()
                    ->where('note_id', $item['note_id'])
                    ->max('version');

                $previousRequest = $this->getActiveRequestFor($item['note_id'], $companyId);

                if ($previousRequest) {
                    $previousRequest->update([
                        'status' => AdsRequestStatus::CANCELED,
                        'canceled_at' => now(),
                        'superseded_by_id' => null,
                    ]);

                    if ($this->sqlSyncEnabled) {
                        $this->syncCanceledToSqlServer($previousRequest);
                    }
                }

                $request = AdsRequest::query()->create([
                    'requested_by' => auth()->id(),
                    'company_id' => $companyId,
                    'note_id' => $item['note_id'],
                    'batch_id' => $batchId,
                    'partner' => false,
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

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => $created . ' solicitacao(oes) criada(s).',
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

            DB::connection('sqlsrv2')
                ->table('sicode.dbo.ads_requests')
                ->insert([
                    'sicode_id' => $request->id,
                    'batch_id' => $request->batch_id,
                    'note' => $noteNumber,
                    'company' => $company?->name,
                    'status' => $request->status->value,
                    'attempts' => $request->attempts ?? 0,
                    'partner' => $request->partner ? 1 : 0,
                    'register' => $user?->Registration,
                    'user' => $user?->name,
                    'email' => $user?->email,
                    'description' => $request->description,
                    'completed_at' => $request->completed_at,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                ]);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function syncCanceledToSqlServer(AdsRequest $request): void
    {
        try {
            DB::connection('sqlsrv2')
                ->table('sicode.dbo.ads_requests')
                ->where('sicode_id', $request->id)
                ->update([
                    'status' => AdsRequestStatus::CANCELED->value,
                    'updated_at' => now(),
                    'completed_at' => $request->canceled_at ?? now(),
                ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    protected function getActiveRequestFor(int $noteId, string $companyId): ?AdsRequest
    {
        return AdsRequest::query()
            ->where('note_id', $noteId)
            ->where('company_id', $companyId)
            ->whereIn('status', $this->activeStatuses())
            ->latest('created_at')
            ->first();
    }

    protected function activeStatuses(): array
    {
        return [
            AdsRequestStatus::QUEUED->value,
            AdsRequestStatus::IN_PROGRESS->value,
            AdsRequestStatus::RETRY->value,
        ];
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

        return 'Existem solicitacoes em andamento que serao canceladas e reagendadas para as notas: <strong>' .
            implode(', ', $list) . $extra . '</strong>. Deseja continuar?';
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
        ExportAdsRequestsHistoryJob::dispatch([
            'start' => $this->historyStart,
            'end' => $this->historyEnd,
            'search' => $this->historySearch,
            'company_id' => $this->historyCompanyId,
        ], (string) auth()->id(), 'responsible');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportacao solicitada. Aguarde a notificacao.',
            'timer' => 3000,
        ]);
    }

    public function getCompanyOptionsProperty()
    {
        $user = auth()->user();

        if (!$user) {
            return collect();
        }

        if ($user->superadm || $user->management) {
            return Company::query()->orderBy('name')->get();
        }

        $companies = collect();
        if ($user->Companies && $user->Companies->isNotEmpty()) {
            $companies = $companies->merge($user->Companies);
        }

        if ($user->Company) {
            $companies->push($user->Company);
        }

        return $companies->unique('id')->sortBy('name')->values();
    }

    public function getActiveRequestsProperty()
    {
        $visibleUserIds = $this->visibleUserIds();
        $query = AdsRequest::query()
            ->with(['note', 'company', 'requestedBy'])
            ->when($visibleUserIds !== null, function ($q) use ($visibleUserIds) {
                $q->whereIn('requested_by', $visibleUserIds);
            })
            ->whereNotIn('status', [
                AdsRequestStatus::DONE->value,
                AdsRequestStatus::CANCELED->value,
                AdsRequestStatus::FAILED->value,
            ])
            ->orderByDesc('created_at');

        if ($this->activeSearch) {
            $search = trim($this->activeSearch);
            $query->whereHas('note', function ($q) use ($search) {
                $q->where('note', 'like', '%' . $search . '%');
            });
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

            if ($request->status === AdsRequestStatus::CANCELED && $sqlRow->status !== AdsRequestStatus::CANCELED->value) {
                $this->syncCanceledToSqlServer($request);
                $updated++;
                continue;
            }

            $request->fill([
                'status' => $sqlRow->status,
                'attempts' => (int) ($sqlRow->attempts ?? 0),
                'description' => $sqlRow->description,
                'url' => $sqlRow->url,
                'completed_at' => $sqlRow->completed_at,
                'sqlserver_id' => $sqlRow->id,
                'completed' => $sqlRow->status === AdsRequestStatus::DONE->value,
                'updated_at' => $sqlRow->updated_at,
            ]);

            if ($request->isDirty()) {
                $request->timestamps = false;
                $request->save();
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

    public function getHistoryRequestsProperty()
    {
        $visibleUserIds = $this->visibleUserIds();
        $query = AdsRequest::query()
            ->with(['note', 'company', 'requestedBy'])
            ->when($visibleUserIds !== null, function ($q) use ($visibleUserIds) {
                $q->whereIn('requested_by', $visibleUserIds);
            })
            ->whereIn('status', [
                AdsRequestStatus::DONE->value,
                AdsRequestStatus::FAILED->value,
                AdsRequestStatus::CANCELED->value,
            ]);

        if ($this->historySearch) {
            $search = trim($this->historySearch);
            $query->whereHas('note', function ($q) use ($search) {
                $q->where('note', 'like', '%' . $search . '%');
            });
        }

        if ($this->historyCompanyId) {
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

        return view('livewire.responsible.ads-requests', [
            'activeRequests' => $activeRequests,
            'sqlStatusBySicodeId' => $sqlStatusBySicodeId,
            'historyRequests' => $this->historyRequests,
            'companyOptions' => $this->companyOptions,
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

    protected function visibleUserIds(): ?\Illuminate\Support\Collection
    {
        $user = auth()->user();

        if (!$user || $user->superadm) {
            return null;
        }

        return $user->descendantsQuery(true)->pluck('users.id');
    }
}
