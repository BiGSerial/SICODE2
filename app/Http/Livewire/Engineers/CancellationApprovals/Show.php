<?php

namespace App\Http\Livewire\Engineers\CancellationApprovals;

use App\Enum\CancellationEngineerApprovalStatus;
use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Show extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'confirm_engineer_cancellation_decide' => 'confirmDecide',
    ];

    public int $requestId;
    public CancellationRequest $cancellationRequest;
    public string $decision = CancellationEngineerApprovalStatus::APPROVED->value;
    public string $reason = '';
    public $files = [];
    public array $tempFiles = [];

    public array $config = [
        'max_size_mb' => 10,
        'allowed_exts' => [
            'jpg','jpeg','png','gif','bmp','svg','tiff','webp',
            'pdf','doc','docx','odt','xls','xlsx','xlsm','ods',
            'dwg','dxf','dws','dwt','dgn','rvt','rfa','skp','txt'
        ],
    ];

    public function mount(int $request): void
    {
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
            'EngineerApprovalRequester',
            'EngineerApprover',
            'EngineerApprovalDecider',
        ])->findOrFail($this->requestId);

        $visibleUserIds = Auth::user()?->visibleUserIdsForWork() ?? collect();

        if (!$visibleUserIds->contains((string) $this->cancellationRequest->engineer_approver_id)) {
            abort(403);
        }
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

    public function decide(): void
    {
        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar decisão',
            'msg' => 'Deseja salvar esta decisão de aprovação de cancelamento?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, salvar decisão',
            'btnCanceltxt' => 'Não, revisar',
            'action' => 'confirm_engineer_cancellation_decide',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'A decisão não foi salva.',
        ]);
    }

    public function confirmDecide(CancellationRequestService $service): void
    {
        if (!trim($this->reason)) {
            $this->addError('reason', 'A justificativa é obrigatória.');
            return;
        }

        try {
            if (!empty($this->tempFiles)) {
                $attachments = array_map(fn ($item) => $item['file'], $this->tempFiles);
                $service->addEvidenceFiles($this->cancellationRequest, Auth::user(), $attachments, 'ENGINEER_APPROVAL');
            }

            $service->decideEngineerApproval($this->cancellationRequest, Auth::user(), $this->decision, $this->reason);

            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Decisão registrada com sucesso.']);

            $this->tempFiles = [];
            $this->files = [];
            $this->reason = '';
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
        return view('livewire.engineers.cancellation-approvals.show');
    }
}
