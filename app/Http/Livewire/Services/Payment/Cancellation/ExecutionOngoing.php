<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Models\CancellationRequest;
use App\Enum\CancellationRequestStatus;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;

class ExecutionOngoing extends Component
{
    use WithPagination;
    use WithFileUploads;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public string $multiSearch = '';
    public ?int $selectedRequestId = null;
    public ?CancellationRequest $selectedRequest = null;

    public string $action = 'DONE';
    public string $comment = '';

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

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updating($field): void
    {
        if ($field === 'multiSearch') {
            $this->resetPage();
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

    public function selectRequest(int $requestId): void
    {
        $this->selectedRequestId = $requestId;
        $this->loadSelectedRequest();
        $this->comment = '';
        $this->action = 'DONE';
        $this->tempFiles = [];
        $this->files = [];
    }

    private function loadSelectedRequest(): void
    {
        if (!$this->selectedRequestId) {
            $this->selectedRequest = null;
            return;
        }

        $this->selectedRequest = CancellationRequest::with([
            'Note',
            'Orders',
            'Category',
            'EvidenceFiles',
            'Comments.User',
            'Requester',
            'Assignee',
        ])->find($this->selectedRequestId);
    }

    public function runAction(CancellationRequestService $service): void
    {
        if (!$this->selectedRequest) {
            return;
        }

        if ($this->selectedRequest->assigned_to !== Auth::id()) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Solicitação não está atribuída a você.']);
            return;
        }

        if (in_array($this->action, ['PAUSED', 'ABORTED'], true) && !trim($this->comment)) {
            $this->addError('comment', 'Comentário obrigatório.');
            return;
        }

        try {
            if (trim($this->comment)) {
                $service->addComment($this->selectedRequest, Auth::user(), $this->comment);
            }

            if (!empty($this->tempFiles)) {
                $attachments = array_map(fn ($item) => $item['file'], $this->tempFiles);
                $service->addEvidenceFiles($this->selectedRequest, Auth::user(), $attachments, 'EXECUCAO_PAGAMENTO');
            }

            if ($this->action === 'DONE') {
                $service->finalizeDone($this->selectedRequest, Auth::user());
            } elseif ($this->action === 'PAUSED') {
                $service->pauseRequest($this->selectedRequest, Auth::user(), $this->comment);
            } else {
                $service->abortRequest($this->selectedRequest, Auth::user(), $this->comment);
            }

            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação atualizada.']);
            $this->selectedRequest = null;
            $this->selectedRequestId = null;
            $this->comment = '';
            $this->action = 'DONE';
            $this->tempFiles = [];
            $this->files = [];
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
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

    public function getListsProperty()
    {
        $multi = $this->parseMultiSearch();

        return CancellationRequest::query()
            ->with(['Note', 'Orders', 'Category', 'Requester'])
            ->where('assigned_to', Auth::id())
            ->whereIn('status', [CancellationRequestStatus::ASSIGNED->value, CancellationRequestStatus::PAUSED->value])
            ->when(count($multi), function ($q) use ($multi) {
                $q->where(function ($sub) use ($multi) {
                    $sub->whereHas('Note', fn ($note) => $note->whereIn('note', $multi))
                        ->orWhereHas('Orders', fn ($order) => $order->whereIn('ordem', $multi));
                });
            })
            ->orderByDesc('assigned_at');
    }

    public function render()
    {
        $lists = $this->lists->paginate(20);
        $this->loadSelectedRequest();

        return view('livewire.services.payment.cancellation.execution-ongoing', [
            'requests' => $lists,
        ]);
    }
}
