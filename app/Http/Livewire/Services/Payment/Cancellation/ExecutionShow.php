<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Enum\CancellationEngineerApprovalStatus;
use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Models\User;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutionShow extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;

    public string $service;
    public int $requestId;
    public CancellationRequest $cancellationRequest;

    public string $action = 'DONE';
    public string $comment = '';
    public ?string $engineerId = null;
    public string $engineerReason = '';

    public $files = [];
    public array $tempFiles = [];

    public array $config = [
        'disk' => 'public',
        'base_path' => 'evidences/CANCELLATION_EXECUTION',
        'max_size_mb' => 10,
        'allowed_exts' => [
            'jpg','jpeg','png','gif','bmp','svg','tiff','webp',
            'pdf','doc','docx','odt','xls','xlsx','xlsm','ods',
            'dwg','dxf','dws','dwt','dgn','rvt','rfa','skp','txt'
        ],
    ];

    public function mount(string $service, int $request): void
    {
        $this->service = $service;
        $this->requestId = $request;
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
            'Comments.User',
            'Requester',
            'Assignee',
            'EngineerApprover',
            'EngineerApprovalRequester',
            'EngineerApprovalDecider',
        ])->findOrFail($this->requestId);

        if ((int) $this->cancellationRequest->assigned_to !== (int) Auth::id()) {
            abort(403);
        }

        $this->engineerId = $this->cancellationRequest->engineer_approver_id;
        $engineerRejected = $this->cancellationRequest->engineer_approval_status === CancellationEngineerApprovalStatus::REJECTED;
        $canFinalize = !$this->cancellationRequest->requires_engineer_approval
            || in_array($this->cancellationRequest->engineer_approval_status?->value, ['APPROVED', 'CANCELED'], true);

        if ($engineerRejected) {
            $this->action = 'ABORTED';
            if (!trim($this->comment)) {
                $this->comment = 'Não autorizado pelo engenheiro.';
            }
            return;
        }

        $this->action = $canFinalize ? 'DONE' : 'PAUSED';
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

    public function runAction(CancellationRequestService $service): void
    {
        if (in_array($this->action, ['PAUSED', 'ABORTED'], true) && !trim($this->comment)) {
            $this->addError('comment', 'Comentário obrigatório.');
            return;
        }

        try {
            if (trim($this->comment)) {
                $service->addComment($this->cancellationRequest, Auth::user(), $this->comment);
            }

            if (!empty($this->tempFiles)) {
                $attachments = array_map(fn ($item) => $item['file'], $this->tempFiles);
                $service->addEvidenceFiles($this->cancellationRequest, Auth::user(), $attachments, 'EXECUCAO_PAGAMENTO');
            }

            if ($this->action === 'DONE') {
                $service->finalizeDone($this->cancellationRequest, Auth::user());
            } elseif ($this->action === 'PAUSED') {
                $service->pauseRequest($this->cancellationRequest, Auth::user(), $this->comment);
            } else {
                $service->abortRequest($this->cancellationRequest, Auth::user(), $this->comment);
            }

            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação atualizada.']);
            $this->comment = '';
            $this->action = 'DONE';
            $this->tempFiles = [];
            $this->files = [];
            $this->loadRequest();
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function requestEngineerApproval(CancellationRequestService $service): void
    {
        if (!$this->engineerId) {
            $this->addError('engineerId', 'Selecione um engenheiro.');
            return;
        }

        try {
            $engineer = User::query()->where('engineer', true)->findOrFail($this->engineerId);
            $service->requestEngineerApproval($this->cancellationRequest, Auth::user(), $engineer, $this->engineerReason);

            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Aprovação enviada para o engenheiro.']);
            $this->engineerReason = '';
            $this->loadRequest();
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function changeEngineer(CancellationRequestService $service): void
    {
        if (!$this->engineerId) {
            $this->addError('engineerId', 'Selecione um engenheiro.');
            return;
        }

        try {
            $engineer = User::query()->where('engineer', true)->findOrFail($this->engineerId);
            $service->changeEngineerApprover($this->cancellationRequest, Auth::user(), $engineer, $this->engineerReason);

            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Engenheiro alterado com sucesso.']);
            $this->engineerReason = '';
            $this->loadRequest();
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function cancelEngineerApproval(CancellationRequestService $service): void
    {
        try {
            $service->cancelEngineerApproval($this->cancellationRequest, Auth::user(), $this->engineerReason);

            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação ao engenheiro cancelada.']);
            $this->engineerReason = '';
            $this->loadRequest();
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
        $engineers = User::query()
            ->where('engineer', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $approvalPending = $this->cancellationRequest->engineer_approval_status === CancellationEngineerApprovalStatus::PENDING;

        return view('livewire.services.payment.cancellation.execution-show', [
            'engineers' => $engineers,
            'approvalPending' => $approvalPending,
        ]);
    }
}
