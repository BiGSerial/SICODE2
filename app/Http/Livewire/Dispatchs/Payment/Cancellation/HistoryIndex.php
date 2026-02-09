<?php

namespace App\Http\Livewire\Dispatchs\Payment\Cancellation;

use App\Jobs\Dispatchs\ExportCancellationHistoryJob;
use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Enum\CancellationRequestStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryIndex extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public string $multiSearch = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $status = null;
    public ?CancellationRequest $noteDetail = null;

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updating($field): void
    {
        if (in_array($field, ['multiSearch', 'dateFrom', 'dateTo', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function exportToExcel(): void
    {
        $this->authorize('viewQueue', CancellationRequest::class);

        ExportCancellationHistoryJob::dispatch($this->exportPayload(), (string) Auth::id());

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
            'status' => $this->status,
        ];
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
        $this->authorize('viewQueue', CancellationRequest::class);

        $this->noteDetail = CancellationRequest::query()
            ->with(['Note', 'Orders', 'EvidenceFiles'])
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
        $this->authorize('viewQueue', CancellationRequest::class);

        $file = EvidenceFile::findOrFail($fileId);
        if ($file->evidenciable_type !== CancellationRequest::class) {
            abort(403);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function render()
    {
        $this->authorize('viewQueue', CancellationRequest::class);

        $multi = $this->parseMultiSearch();

        $history = CancellationRequest::query()
            ->with(['Note', 'Orders', 'Category', 'Requester', 'Assignee', 'Closer'])
            ->whereIn('status', [
                CancellationRequestStatus::DONE->value,
                CancellationRequestStatus::REJECTED->value,
                CancellationRequestStatus::ABORTED->value,
            ])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
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

        return view('livewire.dispatchs.payment.cancellation.history-index', [
            'history' => $history,
        ]);
    }
}
