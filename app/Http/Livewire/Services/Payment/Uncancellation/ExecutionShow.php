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
    public ?string $reversalNote = null;

    protected $listeners = [
        'confirm_uncancellation_execution_run_action' => 'confirmRunAction',
        'confirm_uncancellation_execution_revert_action' => 'confirmRevertAction',
    ];

    public function mount(string $service, int $request): void
    {
        $this->service = $service;
        $this->uncancellationRequest = UncancellationRequest::with(['Note.WorkFormsAny', 'Orders', 'Requester', 'Assignee', 'Events.User'])
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

            $this->uncancellationRequest->refresh()->load(['Note.WorkFormsAny', 'Orders', 'Requester', 'Assignee', 'Events.User']);
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => $message]);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function revertAction(): void
    {
        if (trim((string) $this->reversalNote) === '') {
            $this->addError('reversalNote', 'Informe o motivo para desfazer.');
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Desfazer descancelamento',
            'msg' => "Deseja recancelar os alvos do descancelamento da Nota/OV <strong>" . e($this->uncancellationRequest->Note->note ?? '-') . "</strong>?",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, desfazer',
            'btnCanceltxt' => 'Não, cancelar',
            'action' => 'confirm_uncancellation_execution_revert_action',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'Nenhuma ação foi executada.',
        ]);
    }

    public function confirmRevertAction(UncancellationRequestService $service): void
    {
        try {
            $service->revertDone($this->uncancellationRequest, Auth::user(), (string) $this->reversalNote);
            $this->uncancellationRequest->refresh()->load(['Note.WorkFormsAny', 'Orders', 'Requester', 'Assignee', 'Events.User']);
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Descancelamento desfeito.']);
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
                CancellationRequestStatus::REVERTED,
            ], true),
            'canRevert' => $this->uncancellationRequest->status === CancellationRequestStatus::DONE
                && $this->uncancellationRequest->closure_type === UncancellationRequest::CLOSURE_DONE,
        ]);
    }
}
