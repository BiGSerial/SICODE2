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
    public ?CancellationRequest $request = null;

    public string $action = 'DONE';
    public ?string $closureNote = null;

    public function mount(string $service, int $request): void
    {
        $this->service = $service;
        $this->requestId = $request;
        $this->loadRequest();
    }

    private function loadRequest(): void
    {
        $this->request = CancellationRequest::with([
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
        $this->authorize('claim', $this->request);

        try {
            $service->claimRequest($this->request, Auth::user());
            $this->request->refresh();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação assumida.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function finalize(CancellationRequestService $service): void
    {
        $this->authorize('finalize', $this->request);

        $this->validate([
            'action' => 'required|in:DONE,REJECTED',
            'closureNote' => 'nullable|string|max:2000',
        ]);

        try {
            if ($this->action === 'DONE') {
                $service->finalizeDone($this->request, Auth::user());
            } else {
                if (!$this->closureNote) {
                    $this->addError('closureNote', 'Informe o motivo da rejeição.');
                    return;
                }
                $service->finalizeRejected($this->request, Auth::user(), $this->closureNote);
            }

            $this->request->refresh();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação finalizada.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function downloadEvidence(int $fileId): StreamedResponse
    {
        $file = EvidenceFile::findOrFail($fileId);

        if ($file->evidenciable_type !== CancellationRequest::class || $file->evidenciable_id !== $this->request->id) {
            abort(403);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function render()
    {
        return view('livewire.dispatchs.payment.cancellation.queue-show');
    }
}
