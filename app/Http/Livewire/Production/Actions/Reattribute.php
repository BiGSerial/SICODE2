<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\{Notetimeline, Production};
use Livewire\Component;

class Reattribute extends Component
{
    public ?Production $production;

    public $chave;

    protected $listeners = [
        'confirm_reatt' => 'confirm_reatt',
    ];

    public function mount($production, $chave)
    {
        $this->production = $production;
        $this->chave      = $chave;
    }

    public function ask_reatt()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Re-atribuir',
            'msg'           => "Você deseja reatribuir a Nota/Ov <strong>{$this->production->load('note')->Note->note}</strong> para <strong>{$this->production->load('User')->User->name}</strong>?",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Re-atribua!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_reatt',
            'chave'         => $this->chave,
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/OV foi reatribuída.',
        ]);

    }

    public function confirm_reatt($chave)
    {
        if ($this->chave === $chave) {
            if ($this->production->update(['status' => 2, 'completed' => false])) {
                $this->emit('refresh_list');

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Nota/Ov Re-atribuída com sucesso!',
                    'timer'    => 2500,
                ]);

                $production = $this->production;

                if ($production) {
                    Notetimeline::Create([
                        'note_id'      => $production->id,
                        'service_id'   => $production->service_id,
                        'user_id'      => Auth()->User()->id,
                        'info'         => 'A nota foi reatribuída.',
                        'status'       => 26,
                        'productionId' => $production->id,
                    ]);
                }

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Ocorreu um erro ao tentar re-atribuir a nota/ov.',
                    'timer'    => 5000,
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.production.actions.reattribute');
    }
}
