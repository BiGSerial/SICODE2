<?php

namespace App\Http\Livewire\Services\Payment\Uncancellation;

use App\Enum\CancellationRequestStatus;
use App\Models\UncancellationRequest;
use App\Services\Payment\UncancellationRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

class ExecutionShow extends Component
{
    public string $service;
    public UncancellationRequest $uncancellationRequest;
    public string $action = 'DONE';
    public ?string $closureNote = null;

    protected $listeners = [
        'confirm_uncancellation_execution_run_action' => 'confirmRunAction',
    ];

    public function mount(string $service, int $request): void
    {
        $this->service = $service;
        $this->uncancellationRequest = UncancellationRequest::with(['Note.WorkFormAny', 'Orders', 'Requester', 'Assignee', 'Events.User'])
            ->findOrFail($request);
    }

    public function runAction(): void
    {
        if ($this->action === 'REJECTED' && trim((string) $this->closureNote) === '') {
            $this->addError('closureNote', 'Informe o motivo da rejeição.');
            return;
        }

        $label = $this->action === 'DONE' ? 'concluir o descancelamento' : 'rejeitar a solicitação';

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar descancelamento',
            'msg' => "Deseja {$label} da Nota/OV <strong>" . e($this->uncancellationRequest->Note->note ?? '-') . "</strong>?",
            'icon' => 'question',
            'btnOktxt' => 'Sim, confirmar',
            'btnCanceltxt' => 'Não, cancelar',
            'action' => 'confirm_uncancellation_execution_run_action',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'Nenhuma ação foi executada.',
        ]);
    }

    public function confirmRunAction(UncancellationRequestService $service): void
    {
        try {
            if ($this->action === 'DONE') {
                $service->finalizeDone($this->uncancellationRequest, Auth::user());
                $message = 'Descancelamento concluído.';
            } else {
                $service->finalizeRejected($this->uncancellationRequest, Auth::user(), (string) $this->closureNote);
                $message = 'Solicitação rejeitada.';
            }

            $this->uncancellationRequest->refresh()->load(['Note.WorkFormAny', 'Orders', 'Requester', 'Assignee', 'Events.User']);
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => $message]);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.services.payment.uncancellation.execution-show', [
            'isClosed' => in_array($this->uncancellationRequest->status, [
                CancellationRequestStatus::DONE,
                CancellationRequestStatus::REJECTED,
                CancellationRequestStatus::ABORTED,
            ], true),
        ]);
    }
}
