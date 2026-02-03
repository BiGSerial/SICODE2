<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Enum\CancellationRequestStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyRequestsIndex extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public ?string $status = null;
    public string $search = '';
    public string $historyPeriod = '';
    public ?string $historyStart = null;
    public ?string $historyEnd = null;
    public string $historyNotes = '';
    public ?CancellationRequest $noteDetail = null;

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage('ongoingPage');
    }

    public function updatingStatus(): void
    {
        $this->resetPage('ongoingPage');
    }

    public function updatingHistoryNotes(): void
    {
        $this->resetPage('historyPage');
    }

    public function updatingHistoryStart(): void
    {
        $this->resetPage('historyPage');
    }

    public function updatingHistoryEnd(): void
    {
        $this->resetPage('historyPage');
    }

    public function updatedHistoryPeriod(string $value): void
    {
        if ($value === '') {
            return;
        }

        $days = (int) $value;
        if ($days <= 0) {
            return;
        }

        $this->historyEnd = Carbon::today()->toDateString();
        $this->historyStart = Carbon::today()->subDays($days - 1)->toDateString();
        $this->resetPage('historyPage');
    }

    public function openNoteDetail(int $requestId): void
    {
        $this->authorize('create', CancellationRequest::class);

        $this->noteDetail = CancellationRequest::query()
            ->with(['Note', 'Orders', 'EvidenceFiles'])
            ->where('requested_by', Auth::id())
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
        $this->authorize('create', CancellationRequest::class);

        $file = EvidenceFile::findOrFail($fileId);
        if ($file->evidenciable_type !== CancellationRequest::class) {
            abort(403);
        }

        $request = CancellationRequest::findOrFail($file->evidenciable_id);
        if ((int) $request->requested_by !== (int) Auth::id()) {
            abort(403);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function render()
    {
        $this->authorize('create', CancellationRequest::class);

        $ongoing = CancellationRequest::query()
            ->with(['Note', 'Category', 'Assignee'])
            ->where('requested_by', Auth::id())
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->whereHas('Note', function ($note) {
                    $note->where('note', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'ongoingPage');

        $historyNotes = collect(preg_split('/[\s,;]+/', $this->historyNotes))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $history = CancellationRequest::query()
            ->with(['Note', 'Category', 'Assignee'])
            ->where('requested_by', Auth::id())
            ->whereIn('status', [
                CancellationRequestStatus::DONE->value,
                CancellationRequestStatus::REJECTED->value,
                CancellationRequestStatus::ABORTED->value,
            ])
            ->when($this->historyStart, function ($q) {
                $q->whereDate('closed_at', '>=', $this->historyStart);
            })
            ->when($this->historyEnd, function ($q) {
                $q->whereDate('closed_at', '<=', $this->historyEnd);
            })
            ->when(count($historyNotes), function ($q) use ($historyNotes) {
                $q->whereHas('Note', function ($note) use ($historyNotes) {
                    $note->whereIn('note', $historyNotes);
                });
            })
            ->orderByDesc('closed_at')
            ->paginate(10, ['*'], 'historyPage');

        return view('livewire.services.payment.cancellation.my-requests-index', [
            'ongoing' => $ongoing,
            'history' => $history,
        ]);
    }
}
