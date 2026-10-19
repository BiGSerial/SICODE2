<?php

namespace App\Http\Livewire\Closure\Dispatch\Concerns;

use App\Models\Closure\ClosurePriorityRequest;
use App\Models\{Company, Note, User};
use App\Services\Closure\Analytics\ClosureDispatcherAnalytics;
use App\Services\Closure\{ClosureOrderWorkflowException, ClosureOrderWorkflowService, ClosurePriorityException, ClosurePriorityService};

/**
 * Pedidos de prioridade, priorização de obra e transferência de responsabilidade por Nota.
 * Compartilhado pelo Painel analítico e pela tela de Transferência do despacho.
 * Todas as ações exigem o gate closure.dispatch (garantido no mount do componente).
 */
trait ManagesPriorityAndTransfer
{
    public ?int $transferNoteId = null;

    public ?string $transferUserId = null;

    public string $transferReason = '';

    public array $transferReceipt = [];

    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success';

    public function decidePriority(int $requestId, string $decision, ?string $decisionNote = null): void
    {
        $request = ClosurePriorityRequest::query()->find($requestId);

        if (!$request) {
            $this->feedback('Pedido de prioridade não encontrado.', 'warning');

            return;
        }

        try {
            $service = app(ClosurePriorityService::class);

            if ($decision === 'accept') {
                $service->accept($request, auth()->user(), $decisionNote);
                $this->feedback('Pedido aceito: a prioridade foi aplicada às Ordens abertas da obra.');
            } else {
                $service->decline($request, auth()->user(), $decisionNote);
                $this->feedback('Pedido recusado.');
            }
        } catch (ClosurePriorityException $e) {
            $this->feedback($e->getMessage(), 'warning');
        }
    }

    public function prioritizeNote(int $noteId, bool $priority = true): void
    {
        $note = Note::query()->find($noteId);

        if (!$note) {
            $this->feedback('Obra não encontrada.', 'warning');

            return;
        }

        try {
            $service = app(ClosurePriorityService::class);

            if (!$priority) {
                $count = $service->clear($note, auth()->user());
                $this->feedback("Prioridade removida de {$count} Ordem(ns) da obra.");

                return;
            }

            $pending = ClosurePriorityRequest::query()
                ->where('note_id', $note->id)
                ->where('status', ClosurePriorityRequest::STATUS_PENDING)
                ->first();

            if ($pending) {
                $service->accept($pending, auth()->user(), 'Priorizada diretamente pelo despachante.');
                $this->feedback('Obra priorizada (pedido pendente aceito).');

                return;
            }

            $request = ClosurePriorityRequest::query()->create([
                'note_id'      => $note->id,
                'requested_by' => auth()->id(),
                'reason'       => 'Priorizada diretamente pelo despachante.',
                'status'       => ClosurePriorityRequest::STATUS_PENDING,
            ]);

            $service->accept($request, auth()->user(), 'Priorizada diretamente pelo despachante.');
            $this->feedback('Obra priorizada: Ordens abertas marcadas com prioridade.');
        } catch (ClosurePriorityException $e) {
            $this->feedback($e->getMessage(), 'warning');
        }
    }

    public function openTransfer(int $noteId): void
    {
        $this->transferNoteId  = $noteId;
        $this->transferUserId  = null;
        $this->transferReason  = '';
        $this->transferReceipt = [];
    }

    public function cancelTransfer(): void
    {
        $this->transferNoteId = null;
    }

    public function transfer(): void
    {
        if (!$this->transferNoteId || !$this->transferUserId || trim($this->transferReason) === '') {
            $this->feedback('Informe o usuário de destino e o motivo da transferência.', 'warning');

            return;
        }

        $target = User::query()
            ->whereKey($this->transferUserId)
            ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true)->orWhere('admin', true)->orWhere('superadm', true))
            ->first();
        $company = $target?->company_id ? Company::query()->find($target->company_id) : null;

        if (!$target || !$company) {
            $this->feedback('O usuário de destino precisa ser operador do Encerramento e ter empresa.', 'warning');

            return;
        }

        $workflow = app(ClosureOrderWorkflowService::class);
        $batch    = (string) \Illuminate\Support\Str::uuid();
        $receipt  = ['note_id' => $this->transferNoteId, 'target' => $target->name, 'company' => $company->name, 'reason' => trim($this->transferReason), 'moved' => [], 'skipped' => [], 'at' => now()->format('d/m/Y H:i')];

        foreach ($this->analytics()->transferableStates($this->transferNoteId) as $state) {
            try {
                $workflow->transfer($state, $company, $target, auth()->user(), $this->transferReason, $batch);
                $receipt['moved'][] = $state->Order->ordem;
            } catch (ClosureOrderWorkflowException $e) {
                $receipt['skipped'][] = ['ordem' => $state->Order->ordem, 'reason' => $e->getMessage()];
            }
        }

        $this->transferReceipt = $receipt;
        $this->transferNoteId  = null;
        $this->transferReason  = '';
        $this->feedback(count($receipt['moved']) . ' Ordem(ns) transferida(s) para ' . $target->name . '.', $receipt['moved'] === [] ? 'warning' : 'success');
    }

    public function dismissReceipt(): void
    {
        $this->transferReceipt = [];
    }

    private function analytics(): ClosureDispatcherAnalytics
    {
        return app(ClosureDispatcherAnalytics::class);
    }

    private function feedback(string $message, string $type = 'success'): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType    = $type;
    }
}
