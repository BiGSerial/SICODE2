<?php

namespace App\Http\Livewire\Dispatchs\Payment\Cancellation;

use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QueueShow extends Component
{
    use AuthorizesRequests;

    public string $service;
    public int $requestId;
    public ?CancellationRequest $cancellationRequest = null;

    public string $action = 'DONE';
    public ?string $closureNote = null;

    public function mount(string $service, $request): void
    {
        $this->service = $service;
        $this->requestId = (int) $request;
        $this->loadRequest();
    }

    private function loadRequest(): void
    {
        $this->cancellationRequest = CancellationRequest::with([
            'Note',
            'Orders',
            'Category',
            'EvidenceFiles',
            'Events.Actor',
            'Assignee',
            'Requester',
            'Closer',
        ])->findOrFail($this->requestId);

        $this->authorize('viewQueue', CancellationRequest::class);
    }

    public function claim(CancellationRequestService $service): void
    {
        $this->authorize('claim', $this->cancellationRequest);

        try {
            $service->claimRequest($this->cancellationRequest, Auth::user());
            $this->cancellationRequest->refresh();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação assumida.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function finalize(CancellationRequestService $service): void
    {
        $this->authorize('finalize', $this->cancellationRequest);

        $this->validate([
            'action' => 'required|in:DONE,REJECTED',
            'closureNote' => 'nullable|string|max:2000',
        ]);

        try {
            if ($this->action === 'DONE') {
                $service->finalizeDone($this->cancellationRequest, Auth::user());
            } else {
                if (!$this->closureNote) {
                    $this->addError('closureNote', 'Informe o motivo da rejeição.');
                    return;
                }
                $service->finalizeRejected($this->cancellationRequest, Auth::user(), $this->closureNote);
            }

            $this->cancellationRequest->refresh();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação finalizada.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function downloadEvidence(int $fileId): StreamedResponse
    {
        $file = EvidenceFile::findOrFail($fileId);

        if ($file->evidenciable_type !== CancellationRequest::class || $file->evidenciable_id !== $this->cancellationRequest->id) {
            abort(403);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function render()
    {
        return view('livewire.dispatchs.payment.cancellation.queue-show');
    }
}
