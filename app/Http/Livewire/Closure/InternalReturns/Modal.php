<?php

namespace App\Http\Livewire\Closure\InternalReturns;

use App\Models\{Category, Note, Subcategory};
use App\Models\Closure\{ClosureInternalReturn, ClosureOrderState};
use App\Services\Closure\{ClosureInternalReturnException, ClosureInternalReturnService, ClosureOrderWorkflowService};
use Livewire\Component;

/**
 * Modal do RI (Retorno Interno com o Desenho), aberto pelo menu de Ações da Ordem:
 *  - enviar: devolve a OBRA (Nota) ao Desenho; mostra as Ordens que vão ficar travadas e exige o comentário;
 *  - cancelar (só gestão): destrava as Ordens; o Reclaim do Desenho não é tocado.
 * Regras e contrato: docs/encerramento/CONTRATO_RI_ENCERRAMENTO.md.
 */
class Modal extends Component
{
    protected $listeners = ['closureRiOpen' => 'openSend', 'closureRiCancelOpen' => 'openCancel'];

    /** send | cancel | '' */
    public string $mode = '';

    public ?int $noteId = null;

    public ?int $internalReturnId = null;

    public string $message = '';

    public string $categoryId = '';

    public string $subcategoryId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work') || auth()->user()?->can('closure.manage'), 403);
    }

    public function openSend(int $noteId): void
    {
        $this->reset(['message', 'internalReturnId', 'categoryId', 'subcategoryId']);
        $this->resetErrorBag();
        $this->noteId = $noteId;
        $this->mode   = 'send';
    }

    public function openCancel(int $internalReturnId): void
    {
        $this->reset(['message', 'noteId']);
        $this->resetErrorBag();
        $this->internalReturnId = $internalReturnId;
        $this->mode             = 'cancel';
    }

    public function updatedCategoryId(): void
    {
        $this->subcategoryId = '';
    }

    public function close(): void
    {
        $this->mode = '';
    }

    public function render()
    {
        $note = $this->mode === 'send' && $this->noteId ? Note::query()->find($this->noteId) : null;
        $ri   = $this->mode === 'cancel' && $this->internalReturnId ? ClosureInternalReturn::query()->with('Note')->find($this->internalReturnId) : null;

        return view('livewire.closure.internal-returns.modal', [
            'categories'    => $this->mode === 'send' ? Category::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'subcategories' => $this->mode === 'send' && $this->categoryId !== '' ? Subcategory::query()->where('category_id', (int) $this->categoryId)->orderBy('name')->get(['id', 'name', 'needFile']) : collect(),
            'note'          => $note,
            'ri'            => $ri,
            'states'        => $note ? ClosureOrderState::query()
                ->whereHas('Order', fn ($order) => $order->where('note_id', $note->id))
                ->whereNotIn('operational_status', [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED])
                ->with('Order:id,ordem,note_id')->orderBy('id')->get() : collect(),
        ]);
    }

    public function submit(): void
    {
        if ($this->mode === 'send' && ($this->categoryId === '' || $this->subcategoryId === '')) {
            $this->addError('categoryId', 'Escolha a categoria e a subcategoria do retorno.');

            return;
        }

        if (trim($this->message) === '') {
            $this->addError('message', $this->mode === 'cancel' ? 'Informe o motivo do cancelamento.' : 'Informe o comentário para o Desenho.');

            return;
        }

        try {
            $service = app(ClosureInternalReturnService::class);

            if ($this->mode === 'cancel') {
                $service->cancel(ClosureInternalReturn::query()->findOrFail($this->internalReturnId), auth()->user(), $this->message);
                $title = 'RI cancelado';
                $text  = 'As Ordens da obra foram destravadas. O retorno do Desenho não foi alterado.';
            } else {
                $subcategory = Subcategory::query()->with('Category')->where('category_id', (int) $this->categoryId)->find((int) $this->subcategoryId);

                if (!$subcategory) {
                    $this->addError('categoryId', 'Categoria ou subcategoria inválida.');

                    return;
                }

                $service->send(Note::query()->findOrFail($this->noteId), auth()->user(), $this->message, $subcategory);
                $title = 'RI enviado ao Desenho';
                $text  = 'Todas as Ordens da obra ficam travadas até o Desenho concluir.';
            }
        } catch (ClosureInternalReturnException $exception) {
            $this->addError('message', $exception->getMessage());

            return;
        }

        $this->mode = '';
        $this->emit('closureRiChanged');
        $this->dispatchBrowserEvent('notification:toast', ['title' => $title, 'text' => $text, 'icon' => 'success']);
    }
}
