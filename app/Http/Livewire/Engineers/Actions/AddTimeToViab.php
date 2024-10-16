<?php

namespace App\Http\Livewire\Engineers\Actions;

use App\Models\Viability;
use Livewire\Component;

class AddTimeToViab extends Component
{
    public $viability;
    public $limitDays = 15;
    public $days;
    public $reason;

    protected $listeners = ['addTime'];

    protected $messages = [
        'days.required' => 'O campo dias é obrigatório.',
        'days.numeric' => 'O campo dias deve ser um número.',
        'days.max' => 'O campo dias deve ser no máximo :max.',
        'reason.required' => 'O campo motivo é obrigatório.',
        'reason.string' => 'O campo motivo deve ser uma string.',
        'reason.min' => 'O campo motivo deve ter no mínimo 10 caracteres.',
        'reason.max' => 'O campo motivo deve ter no máximo 255 caracteres.',
    ];


    public function addTime(Viability $viability)
    {
        $this->viability = $viability;



        if ($this->viability) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'addTimeToViabModal',
            ]);
        }
    }

    public function addTimeToViab()
    {
        $this->validate([
            'days' => 'required|numeric|max:' . $this->limitDays,
            'reason' => 'required|string|min:10|max:255',
        ]);

        if (!$this->viability->addDays($this->limitDays, $this->days, $this->reason)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ERRO AO ADICIONAR DIAS.',
                'html'    =>  'Não foi possível adicionar os dias. Verifique se há dias disponíveis ou se você está inserindo uma quantidade válida de dias.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->reason = '';


    }

    public function closeAll()
    {
        $this->days = null;
        $this->reason = null;


        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'addTimeToViabModal',
        ]);

    }

    public function render()
    {
        return view('livewire.engineers.actions.add-time-to-viab');
    }
}
