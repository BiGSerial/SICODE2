<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\{Notetimeline, Production};
use Livewire\Component;

class Priority extends Component
{
    public ?Production $production = null;

    public $chave;

    public $listeners = [
        'give_priority' => 'give_priority',
        'rem_priority'  => 'rem_priority',
    ];

    public function mount($production, $chave)
    {
        $this->production = $production;
        $this->chave      = $chave;
    }

    // public function ask_priority()
    // {
    //     $this->dispatchBrowserEvent('alertar', [
    //         'title' =>  'Priorizar',
    //         'msg' => "Você deseja priorizar <strong>{$this->production->load('note')->Note->note}</strong>?",
    //         'icon' => 'question',
    //         'btnOktxt' => 'Sim, priorize!',
    //         'btnCanceltxt' => 'Não, Cancele',
    //         'action' => "give_priority",
    //         'chave' => $this->chave,
    //         'cancel_titulo' => 'Cancelado!',
    //         'cancel_msg' => 'Nenhuma nenhuma priorização foi definida.',
    //     ]);

    // }

    // public function ask_rem_priority()
    // {
    //     $this->dispatchBrowserEvent('alertar', [
    //         'title' =>  'Remover Prioridade',
    //         'msg' => "Você deseja remover a prioridade de <strong>{$this->production->load('note')->Note->note}</strong>?",
    //         'icon' => 'question',
    //         'btnOktxt' => 'Sim, remova!',
    //         'btnCanceltxt' => 'Não, Cancele',
    //         'action' => "rem_priority",
    //         'chave' => $this->chave,
    //         'cancel_titulo' => 'Cancelado!',
    //         'cancel_msg' => 'Nenhuma nenhuma priorização foi definida.',
    //     ]);

    // }

    // public function give_priority($chave)
    // {

    //     if ($this->chave === $chave) {
    //         if ($this->production->update(['priority' => true])) {
    //             $this->emit('refresh_list');

    //             $this->dispatchBrowserEvent('swal', [
    //                 'position' => 'center',
    //                 'icon' => 'success',
    //                 'title' => 'Nota Priorizada com Sucesso',
    //                 'timer' => 2500,
    //             ]);

    //             $production = $this->production;

    //             if ($production) {
    //                 Notetimeline::Create([
    //                     'note_id' => $production->id,
    //                     'service_id' => $production->service_id,
    //                     'user_id' => Auth()->User()->id,
    //                     'info' => "A Nota foi priorizada",
    //                     'status' => 24,
    //                     'productionId' => $production->id,
    //                 ]);
    //             }
    //         } else {
    //             $this->dispatchBrowserEvent('swal', [
    //                 'position' => 'center',
    //                 'icon' => 'error',
    //                 'title' => 'Erro ao tentar priorizar a nota/ov',
    //                 'timer' => 2500,
    //             ]);
    //         }
    //     }
    // }

    // public function rem_priority($chave)
    // {

    //     if ($this->chave === $chave) {
    //         if ($this->production->update(['priority' => false])) {
    //             $this->emit('refresh_list');

    //             $this->dispatchBrowserEvent('swal', [
    //                 'position' => 'center',
    //                 'icon' => 'success',
    //                 'title' => 'Nota Prioridade removida com Sucesso',
    //                 'timer' => 2500,
    //             ]);

    //             $production = $this->production;

    //             if ($production) {
    //                 Notetimeline::Create([
    //                     'note_id' => $production->id,
    //                     'service_id' => $production->service_id,
    //                     'user_id' => Auth()->User()->id,
    //                     'info' => "A nota foi despriorizada",
    //                     'status' => 25,
    //                     'productionId' => $production->id,
    //                 ]);
    //             }
    //         } else {
    //             $this->dispatchBrowserEvent('swal', [
    //                 'position' => 'center',
    //                 'icon' => 'error',
    //                 'title' => 'Erro ao tentar remover priorizar a nota/ov',
    //                 'timer' => 2500,
    //             ]);
    //         }
    //     }
    // }

    public function render()
    {
        return view('livewire.production.actions.priority');
    }
}
