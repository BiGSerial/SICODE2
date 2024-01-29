<?php

namespace App\Http\Livewire\Components\Modal;

use App\Models\Priority as ModelsPriority;
use App\Models\Production;
use Livewire\Component;

class Priority extends Component
{
    public $priority;
    public $production;
    public $infoPriority;

    protected $listeners = [
        'setPriority' => 'setPriority',
        'confirmPriority' => 'confirmPriority',
        'removePriority' => 'removePriority',
        'confirmRemovePriority' => 'confirmRemovePriority',
        'infoPriority' => 'infoPriority',
    ];

    public function setPriority(Production $production)
    {
        if ($production) {
            $this->production = $production;

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'priorityModal'
            ]);
        }
    }

    public function givePriority()
    {
        if (!trim($this->priority)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'A informação da prioridade é obrigatório.',
                'timer' => 2500,
            ]);

            return;
        }

        if ($this->production) {
            $this->dispatchBrowserEvent('alertar', [
                'title' =>  'Confirmar Prioridade',
                'msg' => "Deseja confirmar prioridade para Nota/OV {$this->production->load('Note')->Note->note}",                'icon' => 'warning',
                'btnOktxt' => 'Sim, Priorize!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => "confirmPriority",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhum nota/ov foi priorizada.',

            ]);
        }
    }

    public function confirmPriority()
    {
        if ($this->production) {
            $priority = ModelsPriority::Create([
                'production_id' => $this->production->id,
                'note_id' => $this->production->note_id,
                'user_id' => Auth()->User()->id,
                'service_id' => $this->production->service_id,
                'prioridade' => $this->priority
            ]);

            if ($priority) {
                $this->production->update(['priority' => true]);

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'success',
                    'title' => 'Prioridade adicionada com sucesso.',
                    'timer' => 2500,
                ]);

                unset($this->production);
                $this->priority = null;

                $this->dispatchBrowserEvent('hideModal');
                $this->emit('refresh_list');


            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'danger',
                    'title' => 'Não foi conseguimos priorizar a note/ov, tente novamente,',
                    'timer' => 2500,
                ]);

                unset($this->production);
                $this->priority = null;

                $this->dispatchBrowserEvent('hideModal');
                $this->dispatchBrowserEvent('hideModal');
            }
        }
    }

    public function removePriority(Production $production)
    {


        if ($production) {

            $this->production = $production;

            $this->dispatchBrowserEvent('alertar', [
                'title' =>  'Confirmar Remover Prioridade',
                'msg' => "Deseja remover prioridade para Nota/OV {$this->production->load('Note')->Note->note}",                'icon' => 'warning',
                'btnOktxt' => 'Sim, Remova!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => "confirmRemovePriority",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhum nota/ov foi removido a prioridade.',

            ]);
        }
    }

    public function confirmRemovePriority()
    {
        if ($this->production) {
            $this->production->update(['priority' => false]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'Prioridade removida com sucesso.',
                'timer' => 2500,
            ]);

            unset($this->production);
            $this->priority = null;

            $this->dispatchBrowserEvent('hideModal');
            $this->emit('refresh_list');

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'danger',
                'title' => 'Não foi conseguimos remover prioridades a note/ov, tente novamente,',
                'timer' => 2500,
            ]);

            unset($this->production);
            $this->priority = null;

            $this->dispatchBrowserEvent('hideModal');
            $this->dispatchBrowserEvent('hideModal');
            $this->emit('refresh_list');
        }


    }

    public function infoPriority($production)
    {
        if ($this->infoPriority = ModelsPriority::where('production_id', $production)->get()->last()) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'infoPrioridade'
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Não encontramos nenhuma informação sobre essa prioridade.',
                'timer' => 2500,
            ]);
        }
    }


    public function render()
    {
        return view('livewire.components.modal.priority');
    }
}
