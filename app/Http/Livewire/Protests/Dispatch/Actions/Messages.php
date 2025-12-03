<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Models\MedProtest;
use Livewire\Component;

class Messages extends Component
{
    public ?MedProtest $medProtest = null;
    public string $message = '';

    protected $listeners = [
        'openMessagesModal',
        'refreshComponent' => '$refresh',
    ];

    protected function rules(): array
    {
        return [
            'message' => 'required|string|max:5000',
        ];
    }

    public function openMessagesModal(MedProtest $medProtest): void
    {
        $this->medProtest = $medProtest->load([
            'protest',
            'comments.user',
        ]);

        $this->message = '';

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'medProtestMessagesModal',
        ]);

        $this->dispatchBrowserEvent('scrollMessagesThread');
    }

    public function sendMessage(): void
    {
        if (!$this->medProtest) {
            return;
        }

        $this->validate();

        $this->medProtest->Comments()->create([
            'user_id' => auth()->id(),
            'message' => trim($this->message),
        ]);

        $this->message = '';

        $this->medProtest->load([
            'comments.user',
            'protest',
        ]);

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Mensagem enviada com sucesso!',
        ]);

        $this->emit('refreshComponent');
    }

    public function closeModal(): void
    {
        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'medProtestMessagesModal',
        ]);

        $this->resetModal();
    }

    protected function resetModal(): void
    {
        $this->reset([
            'medProtest',
            'message',
        ]);
    }

    public function render()
    {
        return view('livewire.protests.dispatch.actions.messages');
    }
}
