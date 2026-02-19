<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestShow extends Component
{
    use AuthorizesRequests;

    public string $service;
    public int $requestId;
    public ?CancellationRequest $cancellationRequest = null;

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
            'EngineerApprover',
            'EngineerApprovalRequester',
            'EngineerApprovalDecider',
        ])->findOrFail($this->requestId);

        $this->authorize('view', $this->cancellationRequest);
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
        return view('livewire.services.payment.cancellation.request-show');
    }
}
