<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Http\Livewire\Closure\Dispatch\Concerns\ManagesPriorityAndTransfer;
use App\Models\{Note, User};
use Livewire\Component;

/**
 * "Transferência de Encerramento" (dispatch.transprod): pedidos de prioridade pendentes e
 * transferência de responsabilidade por obra (Nota). Fina: toda a lógica vem do trait compartilhado
 * com o Painel analítico, que por sua vez usa ClosurePriorityService e ClosureOrderWorkflowService.
 */
class Transfer extends Component
{
    use ManagesPriorityAndTransfer;

    public string $noteSearch = '';

    public int $staleDays = 7;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    public function render()
    {
        $analytics = $this->analytics();
        $term      = trim($this->noteSearch);

        $notes = $term === ''
            ? collect()
            : Note::query()
                ->where('note', 'like', '%' . addcslashes($term, '%_\\') . '%')
                ->whereHas('Orders.ClosureState')
                ->orderBy('note')
                ->limit(10)
                ->get(['id', 'note']);

        return view('livewire.closure.dispatch.transfer', [
            'priorities'    => $analytics->pendingPriorityRequests(),
            'critical'      => $analytics->criticalWorks(max(1, $this->staleDays)),
            'notes'         => $notes,
            'transferUsers' => $this->transferNoteId
                ? User::query()->whereNotNull('company_id')
                    ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true))
                    ->orderBy('name')->get(['id', 'name'])
                : collect(),
            'transferStates' => $this->transferNoteId ? $analytics->transferableStates($this->transferNoteId) : collect(),
        ]);
    }
}
