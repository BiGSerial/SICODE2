<?php

namespace App\Http\Livewire\Partner;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\Note;
use App\Models\SicodeSql\AdsRequest as SqlAdsRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AdsRequests extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $notesInput = '';
    public $previewItems = [];
    public $activeSearch = '';

    public $historyStart;
    public $historyEnd;
    public $historyPerPage = 25;
    public $historySearch = '';

    public function mount()
    {
        $this->historyStart = now()->subDays(30)->toDateString();
        $this->historyEnd = now()->toDateString();
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

    public function updatedActiveSearch()
    {
        // active list is not paginated; just force refresh
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
                'title' => 'Usuario sem empresa vinculada.',
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

            $previousRequest = AdsRequest::query()
                ->where('note_id', $note->id)
                ->where('company_id', $companyId)
                ->whereNotIn('status', [AdsRequestStatus::CANCELED->value])
                ->latest('created_at')
                ->first();

            if ($previousRequest) {
                $items[$noteNumber] = [
                    'note_number' => $noteNumber,
                    'note_id' => $note->id,
                    'status_label' => 'Cancelar anterior',
                    'status_class' => 'text-bg-warning',
                    'message' => 'Pedido anterior sera cancelado.',
                    'can_process' => true,
                    'previous_request_id' => $previousRequest->id,
                    'previous_status' => $previousRequest->status?->label(),
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

    public function clearPreview()
    {
        $this->previewItems = [];
    }

    public function removeAllPreview()
    {
        $this->previewItems = [];
    }

    public function processRequests()
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

        $companyId = $this->resolveCompanyId();
        if (!$companyId) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Usuario sem empresa vinculada.',
                'timer' => 4000,
            ]);
            return;
        }

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

                $previousRequest = AdsRequest::query()
                    ->where('note_id', $item['note_id'])
                    ->where('company_id', $companyId)
                    ->whereNotIn('status', [AdsRequestStatus::CANCELED->value])
                    ->where('id', '!=', $request->id)
                    ->latest('created_at')
                    ->first();

                if ($previousRequest) {
                    $previousRequest->update([
                        'status' => AdsRequestStatus::CANCELED,
                        'canceled_at' => now(),
                        'superseded_by_id' => $request->id,
                    ]);

                    $this->syncCanceledToSqlServer($previousRequest);
                }

                $toMirror[] = [
                    'request' => $request,
                    'note_number' => $item['note_number'],
                ];

                $created++;
            }
        });

        foreach ($toMirror as $payload) {
            if (!$this->mirrorToSqlServer($payload['request'], $payload['note_number'])) {
                $mirrorFailures[] = $payload['note_number'];
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

    public function clearHistoryFilters()
    {
        $this->historyStart = null;
        $this->historyEnd = null;
        $this->resetPage('historyPage');
    }

    public function getActiveRequestsProperty()
    {
        $query = AdsRequest::query()
            ->with(['note', 'company'])
            ->when(!auth()->user()?->superadm, function ($q) {
                $q->where('requested_by', auth()->id());
            })
            ->whereNotIn('status', [AdsRequestStatus::DONE->value, AdsRequestStatus::CANCELED->value])
            ->orderByDesc('created_at');

        if ($this->activeSearch) {
            $search = trim($this->activeSearch);
            $query->whereHas('note', function ($q) use ($search) {
                $q->where('note', 'like', '%' . $search . '%');
            });
        }

        return $query->get();
    }

    public function syncAllRequests()
    {
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

        $sqlRows = SqlAdsRequest::query()
            ->whereIn('sicode_id', $requests->pluck('id'))
            ->get()
            ->keyBy('sicode_id');

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
                'attempts' => $sqlRow->attempts,
                'description' => $sqlRow->description,
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

    public function syncRequest(int $id)
    {
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

        $sqlRow = SqlAdsRequest::query()
            ->where('sicode_id', $request->id)
            ->latest('updated_at')
            ->first();

        if (!$sqlRow) {
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

        $request->fill([
            'status' => $sqlRow->status,
            'attempts' => $sqlRow->attempts,
            'description' => $sqlRow->description,
            'completed_at' => $sqlRow->completed_at,
            'sqlserver_id' => $sqlRow->id,
            'completed' => $sqlRow->status === AdsRequestStatus::DONE->value,
            'updated_at' => $sqlRow->updated_at,
        ]);

        if (!$request->isDirty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'info',
                'title' => 'Sem atualizacoes para esta solicitacao.',
                'timer' => 3000,
            ]);
            return;
        }

        $request->timestamps = false;
        $request->save();

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
            $search = trim($this->historySearch);
            $query->whereHas('note', function ($q) use ($search) {
                $q->where('note', 'like', '%' . $search . '%');
            });
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
        $sqlStatusBySicodeId = SqlAdsRequest::query()
            ->whereIn('sicode_id', $activeRequests->pluck('id'))
            ->get(['sicode_id', 'status'])
            ->keyBy('sicode_id');

        return view('livewire.partner.ads-requests', [
            'activeRequests' => $activeRequests,
            'sqlStatusBySicodeId' => $sqlStatusBySicodeId,
            'historyRequests' => $this->historyRequests,
        ]);
    }
}
