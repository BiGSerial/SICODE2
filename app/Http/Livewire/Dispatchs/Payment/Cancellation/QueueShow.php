<?php

namespace App\Http\Livewire\Dispatchs\Payment\Cancellation;

use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Models\ServiceUser;
use App\Models\User;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QueueShow extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $service;
    public int $requestId;
    public ?CancellationRequest $cancellationRequest = null;

    public string $action = 'DONE';
    public ?string $closureNote = null;
    public ?string $abortReason = null;

    public bool $editing = false;
    public string $editScope = CancellationRequest::SCOPE_NOTE_FULL;
    public ?int $editCategoryId = null;
    public ?string $editDescription = null;
    public array $editSelectedOrders = [];
    public array $editOrders = [];
    public array $removeEvidenceIds = [];

    public $files = [];
    public array $tempFiles = [];

    public ?int $transferUserId = null;

    public array $config = [
        'disk' => 'public',
        'base_path' => 'evidences/CANCELLATION_CONTROL',
        'max_size_mb' => 10,
        'allowed_exts' => [
            'jpg','jpeg','png','gif','bmp','svg','tiff','webp',
            'pdf','doc','docx','odt','xls','xlsx','xlsm','ods',
            'dwg','dxf','dws','dwt','dgn','rvt','rfa','skp','txt'
        ],
    ];

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

    public function updatedFiles(): void
    {
        $maxKb = $this->config['max_size_mb'] * 1024;
        $mimes = implode(',', $this->config['allowed_exts']);
        $this->validate([
            'files.*' => "nullable|file|mimes:{$mimes}|max:{$maxKb}",
        ]);

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

    public function toggleRemoveEvidence(int $fileId): void
    {
        if (in_array($fileId, $this->removeEvidenceIds, true)) {
            $this->removeEvidenceIds = array_values(array_diff($this->removeEvidenceIds, [$fileId]));
            return;
        }

        $this->removeEvidenceIds[] = $fileId;
    }

    public function startEdit(): void
    {
        $this->authorize('edit', $this->cancellationRequest);

        $this->editing = true;
        $this->editScope = $this->cancellationRequest->scope;
        $this->editCategoryId = $this->cancellationRequest->category_id;
        $this->editDescription = $this->cancellationRequest->description;
        $this->editSelectedOrders = $this->cancellationRequest->Orders->pluck('id')->all();
        $this->editOrders = $this->cancellationRequest->Note->Orders->map(function ($order) {
            return [
                'id' => $order->id,
                'ordem' => $order->ordem,
                'status' => $order->statusUser ?? $order->statusSist,
                'canceled' => (bool) $order->canceled,
            ];
        })->toArray();
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->editScope = CancellationRequest::SCOPE_NOTE_FULL;
        $this->editCategoryId = null;
        $this->editDescription = null;
        $this->editSelectedOrders = [];
        $this->editOrders = [];
        $this->removeEvidenceIds = [];
        $this->tempFiles = [];
        $this->files = [];
    }

    public function saveEdit(CancellationRequestService $service): void
    {
        $this->authorize('edit', $this->cancellationRequest);

        if ($this->editScope === CancellationRequest::SCOPE_NOTE_FULL) {
            $this->editSelectedOrders = collect($this->editOrders)->pluck('id')->all();
        }

        if ($this->editScope === CancellationRequest::SCOPE_ORDERS_PARTIAL && empty($this->editSelectedOrders)) {
            $this->addError('editSelectedOrders', 'Selecione ao menos uma ordem.');
            return;
        }

        $category = \App\Models\CancellationCategory::find($this->editCategoryId);
        if (!$category) {
            $this->addError('editCategoryId', 'Categoria inválida.');
            return;
        }

        try {
            $attachments = array_map(fn ($item) => $item['file'], $this->tempFiles);
            $service->updateRequest(
                $this->cancellationRequest,
                Auth::user(),
                $this->editScope,
                $category,
                $this->editSelectedOrders,
                $attachments,
                $this->removeEvidenceIds,
                $this->editDescription
            );

            $this->cancellationRequest->refresh();
            $this->cancelEdit();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação atualizada.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
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

    public function abort(CancellationRequestService $service): void
    {
        $this->authorize('abort', $this->cancellationRequest);

        try {
            $service->abortRequest($this->cancellationRequest, Auth::user(), $this->abortReason);
            $this->cancellationRequest->refresh();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação cancelada.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function transfer(CancellationRequestService $service): void
    {
        $this->authorize('transfer', $this->cancellationRequest);

        $target = User::find($this->transferUserId);
        if (!$target) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Selecione um usuário válido.']);
            return;
        }

        try {
            $service->transferRequest($this->cancellationRequest, Auth::user(), $target);
            $this->cancellationRequest->refresh();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação transferida.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function deleteRequest(CancellationRequestService $service): void
    {
        $this->authorize('delete', $this->cancellationRequest);

        try {
            $service->deleteRequest($this->cancellationRequest, Auth::user());
            $this->redirectRoute('dispatch.cancellation.queue', ['service' => $this->service], true);
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
        $paymentUsers = ServiceUser::query()
            ->with('User')
            ->where('service_id', $this->service)
            ->where('dispatch', true)
            ->get()
            ->pluck('User')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('livewire.dispatchs.payment.cancellation.queue-show', [
            'paymentUsers' => $paymentUsers,
        ]);
    }
}
