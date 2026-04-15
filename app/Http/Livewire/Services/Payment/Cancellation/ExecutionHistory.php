<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Enum\CancellationRequestStatus;
use App\Jobs\Services\ExportCancellationExecutionHistoryJob;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutionHistory extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public string $multiSearch = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $visibilityMode = 'HIERARCHY';
    public array $requesterIds = [];
    public ?CancellationRequest $noteDetail = null;

    public function mount(string $service): void
    {
        $this->service = $service;

        if ((Auth::user()?->superadm || Auth::user()?->management) && !request()->has('vis')) {
            $this->visibilityMode = 'ALL';
        }
    }

    public function updating($field): void
    {
        if (in_array($field, ['multiSearch', 'dateFrom', 'dateTo', 'visibilityMode', 'requesterIds'], true)) {
            $this->resetPage();
        }
    }

    private function parseMultiSearch(): array
    {
        return collect(preg_split('/[\s,;\n\r]+/', $this->multiSearch))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function openNoteDetail(int $requestId): void
    {
        $visibleCloserIds = $this->visibleCloserIds();
        $this->noteDetail = CancellationRequest::query()
            ->with(['Note', 'Orders', 'EvidenceFiles'])
            ->when($visibleCloserIds !== null, fn ($q) => $q->whereIn('closed_by', $visibleCloserIds))
            ->findOrFail($requestId);

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'modal_note_detail',
        ]);
    }

    public function closeNoteDetail(): void
    {
        $this->noteDetail = null;
    }

    public function downloadEvidence(int $fileId): StreamedResponse
    {
        $file = EvidenceFile::findOrFail($fileId);
        if ($file->evidenciable_type !== CancellationRequest::class) {
            abort(403);
        }

        $request = CancellationRequest::findOrFail($file->evidenciable_id);
        $visibleCloserIds = $this->visibleCloserIds();
        if ($visibleCloserIds !== null && !in_array((string) $request->closed_by, $visibleCloserIds, true)) {
            abort(403);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    private function visibleCloserIds(): ?array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($this->visibilityMode === 'ALL') {
            return null;
        }

        if ($this->visibilityMode === 'SUCCESSION') {
            return $user->descendantsQuery(
                includeSelf: true,
                includeDelegations: false,
                includeDelegatesTreesForPrincipal: true
            )->pluck('users.id')->unique()->values()->map(fn ($id) => (string) $id)->all();
        }

        return $user->descendantsQuery(
            includeSelf: true,
            includeDelegations: true,
            includeDelegatesTreesForPrincipal: false
        )->pluck('users.id')->unique()->values()->map(fn ($id) => (string) $id)->all();
    }

    private function selectedRequesterIds(): array
    {
        return collect($this->requesterIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    public function exportToExcel(): void
    {
        ExportCancellationExecutionHistoryJob::dispatch($this->exportPayload(), (string) Auth::id());

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Exportação iniciada. Você será notificado quando concluir.',
        ]);
    }

    private function exportPayload(): array
    {
        return [
            'service_uuid' => $this->service,
            'multiSearch' => $this->parseMultiSearch(),
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'visibilityMode' => $this->visibilityMode,
            'visibleCloserIds' => $this->visibleCloserIds(),
            'requesterIds' => $this->selectedRequesterIds(),
        ];
    }

    public function render()
    {
        $multi = $this->parseMultiSearch();
        $visibleCloserIds = $this->visibleCloserIds();
        $selectedRequesterIds = $this->selectedRequesterIds();

        $history = CancellationRequest::query()
            ->with(['Note', 'Orders', 'Category', 'Requester', 'Closer'])
            ->when($visibleCloserIds !== null, fn ($q) => $q->whereIn('closed_by', $visibleCloserIds))
            ->when(count($selectedRequesterIds), fn ($q) => $q->whereIn('requested_by', $selectedRequesterIds))
            ->whereIn('status', [
                CancellationRequestStatus::DONE->value,
                CancellationRequestStatus::REJECTED->value,
                CancellationRequestStatus::ABORTED->value,
            ])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('closed_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('closed_at', '<=', $this->dateTo))
            ->when(count($multi), function ($q) use ($multi) {
                $q->where(function ($sub) use ($multi) {
                    $sub->whereHas('Note', fn ($note) => $note->whereIn('note', $multi))
                        ->orWhereHas('Orders', fn ($order) => $order->whereIn('ordem', $multi));
                });
            })
            ->orderByDesc('closed_at')
            ->paginate(20);

        $requesterOptions = DB::table('users as u')
            ->join('cancellation_requests as cr', 'cr.requested_by', '=', 'u.id')
            ->whereIn('cr.status', [
                CancellationRequestStatus::DONE->value,
                CancellationRequestStatus::REJECTED->value,
                CancellationRequestStatus::ABORTED->value,
            ])
            ->when($visibleCloserIds !== null, fn ($q) => $q->whereIn('cr.closed_by', $visibleCloserIds))
            ->select('u.id', 'u.name')
            ->distinct()
            ->orderByRaw('LOWER(u.name)')
            ->get();

        return view('livewire.services.payment.cancellation.execution-history', [
            'history' => $history,
            'requesterOptions' => $requesterOptions,
            'visibilityOptions' => [
                ['value' => 'ALL', 'label' => 'Tudo'],
                ['value' => 'HIERARCHY', 'label' => 'Minha hierarquia'],
                ['value' => 'SUCCESSION', 'label' => 'Linha de sucessão'],
            ],
        ]);
    }
}
