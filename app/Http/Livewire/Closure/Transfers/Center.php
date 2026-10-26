<?php

namespace App\Http\Livewire\Closure\Transfers;

use App\Models\Closure\ClosureTransferRequest;
use App\Services\Closure\{ClosureTransferException, ClosureTransferService};
use Livewire\Component;

/**
 * Central de transferências entre usuários do Encerramento: o que chegou para mim (aceitar/rejeitar), o que enviei (cancelar)
 * e o histórico. Sem aprovação da gestão — quem recebe decide.
 */
class Center extends Component
{
    protected $listeners = ['closureTransferSent' => '$refresh'];

    public string $tab = 'received';

    /** Pedido que está sendo rejeitado (para pedir o motivo opcional). */
    public ?int $rejectingId = null;

    public string $rejectNote = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);
    }

    public function render()
    {
        $me   = (string) auth()->id();
        $base = fn () => ClosureTransferRequest::query()->with(['From', 'To', 'Items.State.Order.Note'])->latest('id');

        return view('livewire.closure.transfers.center', [
            'received' => $base()->where('to_user_id', $me)->where('status', ClosureTransferRequest::STATUS_PENDING)->get(),
            'sent'     => $base()->where('from_user_id', $me)->where('status', ClosureTransferRequest::STATUS_PENDING)->get(),
            'history'  => $this->tab === 'history'
                ? $base()->where(fn ($q) => $q->where('from_user_id', $me)->orWhere('to_user_id', $me))->where('status', '!=', ClosureTransferRequest::STATUS_PENDING)->limit(50)->get()
                : collect(),
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->tab         = in_array($tab, ['received', 'sent', 'history'], true) ? $tab : 'received';
        $this->rejectingId = null;
    }

    public function accept(int $id): void
    {
        $this->run(function (ClosureTransferService $service, ClosureTransferRequest $request) {
            $done  = $service->accept($request, auth()->user());
            $moved = $done->Items->where('result', 'MOVED')->count();
            $skip  = $done->Items->where('result', 'SKIPPED')->count();

            $this->toast('Transferência aceita', "{$moved} Ordem(ns) agora estão com você" . ($skip ? ", {$skip} não puderam ser movidas (veja no histórico)." : '.'), $skip && !$moved ? 'warning' : 'success');
        }, $id);
    }

    public function askReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectNote  = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
    }

    public function reject(): void
    {
        if (!$this->rejectingId) {
            return;
        }

        $id = $this->rejectingId;

        $this->run(function (ClosureTransferService $service, ClosureTransferRequest $request) {
            $service->reject($request, auth()->user(), $this->rejectNote);
            $this->toast('Transferência rejeitada', 'As Ordens continuam com quem enviou.');
        }, $id);

        $this->rejectingId = null;
    }

    public function cancel(int $id): void
    {
        $this->run(function (ClosureTransferService $service, ClosureTransferRequest $request) {
            $service->cancel($request, auth()->user());
            $this->toast('Transferência cancelada', 'As Ordens continuam com você.');
        }, $id);
    }

    private function run(callable $action, int $id): void
    {
        $request = ClosureTransferRequest::query()->find($id);

        if (!$request) {
            $this->toast('Não encontrada', 'Esta transferência não existe mais.', 'warning');

            return;
        }

        try {
            $action(app(ClosureTransferService::class), $request);
        } catch (ClosureTransferException $exception) {
            $this->toast('Não foi possível', $exception->getMessage(), 'warning');
        }
    }

    private function toast(string $title, string $text, string $icon = 'success'): void
    {
        $this->dispatchBrowserEvent('notification:toast', ['title' => $title, 'text' => $text, 'icon' => $icon]);
    }
}
