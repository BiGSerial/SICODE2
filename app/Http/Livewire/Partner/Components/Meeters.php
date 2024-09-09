<?php

namespace App\Http\Livewire\Partner\Components;

use App\Models\Meeter;
use App\Models\WorkReport;
use Livewire\Component;

class Meeters extends Component
{
    public ?WorkReport $workReport = null;

    public $listeners = [
        'refresh_me' => '$refresh'
    ];

    public $model_meeter = [
        'number' => null,
        'borne' => null,
        'fases' => null,
    ];


    protected $rules = [
        'model_meeter.number' => 'required|string|max:255',
        'model_meeter.borne' => 'required|string|max:255',
        'model_meeter.fases' => 'required|string|max:255',

    ];

    protected $messages = [
        'model_meeter.number.required' => 'O campo Numero de Medidor é obrigatório.',
        'model_meeter.number.string' => 'O campo Numero de Medidor deve ser um texto.',
        'model_meeter.number.size' => 'O campo Numero de Medidor deve ter exatamente 2 caracteres.',

        'model_meeter.borne.required' => 'O campo borne é obrigatório.',
        'model_meeter.borne.string' => 'O campo borne deve ser um texto alfanumérico.',
        'model_meeter.borne.max' => 'O campo fases não pode ter mais de 255 caracteres.',

        'model_meeter.fases.required' => 'O campo poste é obrigatório.',
    ];


    public function mount(WorkReport $workReport)
    {
        $this->workReport = $workReport;

        $this->reset('model_meeter');
        $this->resetErrorBag();
    }

    public function updatedModelMeeter($value, $key)
    {
        if (in_array($key, ['number', 'borne'])) {
            $this->model_meeter[$key] = trim(strtoupper($value));
        }
    }

    // Interacting to Add and remove Equipments in WorkForm
    public function remMeeters(Meeter $meeter)
    {
        $meeter->delete();
        $this->emitSelf('refresh_me');
    }

    public function addMeeters()
    {
        $this->validate();

        $this->workReport->Meeters()->updateOrCreate(
            [
                'number' => $this->model_meeter['number'],

            ],
            [
                'borne' => $this->model_meeter['borne'],
                'fases' => $this->model_meeter['fases'],
            ]
        );

        $this->emitSelf('refresh_me');



        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.partner.components.meeters');
    }
}
