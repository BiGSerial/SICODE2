<?php

namespace App\Http\Livewire\Closure\Transfers;

use App\Models\Closure\ClosureOrderState;
use App\Models\User;
use App\Services\Closure\{ClosureServiceLocator, ClosureTransferException, ClosureTransferService};
use Livewire\Component;

/** Modal "Transferir para outro usuário": escolhe o destinatário do Encerramento e o motivo; ele aceita ou rejeita. */
class SendModal extends Component
{
    protected $listeners = ['closureTransferOpen' => 'open'];

    public bool $show = false;

    /** @var array<int, int> */
    public array $stateIds = [];

    public ?string $toUserId = null;

    public string $reason = '';

    public string $userSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);
    }

    /** @param array<int, int|string> $stateIds */
    public function open(array $stateIds): void
    {
        $this->stateIds   = array_values(array_unique(array_map('intval', $stateIds)));
        $this->toUserId   = null;
        $this->reason     = '';
        $this->userSearch = '';
        $this->show       = $this->stateIds !== [];
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.closure.transfers.send-modal', [
            'states' => $this->show ? ClosureOrderState::query()->whereIn('id', $this->stateIds)->with('Order.Note')->get() : collect(),
            'users'  => $this->show ? $this->destinationUsers() : collect(),
        ]);
    }

    public function send(): void
    {
        if (!$this->toUserId) {
            $this->addError('toUserId', 'Escolha quem vai receber.');

            return;
        }

        if (trim($this->reason) === '') {
            $this->addError('reason', 'Informe o motivo.');

            return;
        }

        try {
            $request = app(ClosureTransferService::class)->send(auth()->user(), User::query()->findOrFail($this->toUserId), $this->stateIds, $this->reason);
        } catch (ClosureTransferException $exception) {
            $this->addError('send', $exception->getMessage());

            return;
        }

        $this->show = false;
        $this->emit('closureTransferSent');
        $this->dispatchBrowserEvent('notification:toast', [
            'title' => 'Transferência enviada',
            'text'  => 'Aguardando ' . $request->To?->name . ' aceitar. Você pode cancelar em Transferências.',
            'icon'  => 'success',
        ]);
    }

    /** Usuários do Encerramento (com empresa), exceto quem envia. A regra definitiva é do service. */
    private function destinationUsers()
    {
        $term = trim($this->userSearch);

        return User::query()
            ->whereNotNull('company_id')
            ->whereKeyNot(auth()->id())
            ->where(function ($q) {
                $q->where('closure_operator', true)->orWhere('closure_manager', true)->orWhere('admin', true)->orWhere('superadm', true)
                    ->orWhereHas('ToServices', fn ($s) => $s->whereHas('Service', fn ($service) => $service->where('folder', ClosureServiceLocator::FOLDER)));
            })
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%' . addcslashes($term, '%_\\') . '%'))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);
    }
}
