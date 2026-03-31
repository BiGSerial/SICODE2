<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Jobs\Services\ExportCancellationExecutionOrdersJob;
use App\Models\CancellationRequest;
use App\Services\Payment\CancellationRequestService;
use App\Support\EvidenceFileUploader;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class ExecutionBulk extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;
    protected $listeners = [
        'confirm_cancellation_execution_bulk_run_action' => 'confirmRunBulkAction',
    ];

    public string $service;
    public array $ids = [];

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

    public function mount(string $service, ?string $ids = null): void
    {
        $this->service = $service;
        $this->ids = $this->parseIds($ids ?? request()->query('ids', ''));
    }

    private function parseIds(string $value): array
    {
        return collect(preg_split('/[\s,;\n\r]+/', $value))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
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

    public function exportOrders(): void
    {
        if (empty($this->ids)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhum registro selecionado.']);
            return;
        }

        ExportCancellationExecutionOrdersJob::dispatch([
            'ids' => $this->ids,
            'user_id' => (string) Auth::id(),
        ]);

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Exportação iniciada. Você será notificado quando concluir.',
        ]);
    }

    public function runBulkAction(): void
    {
        if (empty($this->ids)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhum registro selecionado.']);
            return;
        }

        if (in_array($this->action, ['PAUSED', 'ABORTED'], true) && !trim($this->comment)) {
            $this->addError('comment', 'Comentário obrigatório.');
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar ação em massa',
            'msg' => 'Deseja executar a ação para todas as solicitações selecionadas?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, confirmar',
            'btnCanceltxt' => 'Não, revisar',
            'action' => 'confirm_cancellation_execution_bulk_run_action',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'Nenhuma ação foi executada.',
        ]);
    }

    public function confirmRunBulkAction(CancellationRequestService $service): void
    {
        if (empty($this->ids)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhum registro selecionado.']);
            return;
        }

        if (in_array($this->action, ['PAUSED', 'ABORTED'], true) && !trim($this->comment)) {
            $this->addError('comment', 'Comentário obrigatório.');
            return;
        }

        $requests = CancellationRequest::query()
            ->with(['Note', 'Orders'])
            ->whereIn('id', $this->ids)
            ->where('assigned_to', Auth::id())
            ->get();

        $uploader = new EvidenceFileUploader();
        $sharedFiles = [];
        if (!empty($this->tempFiles)) {
            foreach ($this->tempFiles as $item) {
                $sharedFiles[] = $uploader->storeSharedCancellationFile($item['file'], 'multi');
            }
        }

        $processed = 0;
        $errors = 0;

        foreach ($requests as $request) {
            try {
                if (trim($this->comment)) {
                    $service->addComment($request, Auth::user(), $this->comment);
                }

                foreach ($sharedFiles as $data) {
                    $service->attachSharedEvidence($request, Auth::user(), $data, 'EXECUCAO_PAGAMENTO');
                }

                if ($this->action === 'DONE') {
                    $service->finalizeDone($request, Auth::user());
                } elseif ($this->action === 'PAUSED') {
                    $service->pauseRequest($request, Auth::user(), $this->comment);
                } else {
                    $service->abortRequest($request, Auth::user(), $this->comment);
                }

                $processed++;
            } catch (RuntimeException $e) {
                $errors++;
            }
        }

        $this->dispatchBrowserEvent('swal', [
            'icon' => $errors ? 'warning' : 'success',
            'title' => "Processadas: {$processed}. Erros: {$errors}.",
        ]);
    }

    public function render()
    {
        $requests = CancellationRequest::query()
            ->with(['Note', 'Orders', 'Category'])
            ->whereIn('id', $this->ids)
            ->where('assigned_to', Auth::id())
            ->get();

        return view('livewire.services.payment.cancellation.execution-bulk', [
            'requests' => $requests,
        ]);
    }
}
