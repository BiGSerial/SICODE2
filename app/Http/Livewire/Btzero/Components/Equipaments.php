<?php

namespace App\Http\Livewire\Btzero\Components;

use App\Models\BtzeroEquipment;
use App\Models\RamalReport;
use Livewire\Component;

class Equipaments extends Component
{
    public ?RamalReport $workRP = null;

    public $listeners = [
        'refresh_me' => '$refresh'
    ];

    public $model_equipment = [
        'type' => null,
        'patrimony' => null,
        'fases' => null,
        'pole' => null,
        'installed' => false,
    ];

    protected $rules = [
        'model_equipment.type' => 'required|string|size:2',
        'model_equipment.patrimony' => 'required|string|max:255',
        'model_equipment.fases' => 'required|string|max:255',
        'model_equipment.pole' => 'required|string|max:15',
        'model_equipment.installed' => 'required|boolean',

    ];

    protected $messages = [
        'model_equipment.type.required' => 'O campo tipo de equipamento é obrigatório.',
        'model_equipment.type.string' => 'O campo tipo de equipamento deve ser um texto.',
        'model_equipment.type.size' => 'O campo tipo de equipamento deve ter exatamente 2 caracteres.',

        'model_equipment.patrimony.required' => 'O campo patrimônio é obrigatório.',
        'model_equipment.patrimony.numeric' => 'O campo patrimônio deve conter apenas números.',

        'model_equipment.fases.required' => 'O campo fases é obrigatório.',
        'model_equipment.fases.string' => 'O campo fases deve ser um texto.',
        'model_equipment.fases.max' => 'O campo fases não pode ter mais de 255 caracteres.',

        'model_equipment.pole.required' => 'O campo poste é obrigatório.',
        'model_equipment.pole.string' => 'O campo poste deve ser um texto.',
        'model_equipment.pole.max' => 'O campo poste não pode ter mais de 15 caracteres.',

        'model_equipment.installed.required' => 'O campo instalado é obrigatório.',
        'model_equipment.installed.boolean' => 'O campo instalado deve ser verdadeiro ou falso.',
    ];

    public function mount(RamalReport $workReport)
    {



        $this->workRP = $workReport;

        $this->reset('model_equipment');
        $this->resetErrorBag();
    }

    public function updatedModelEquipment($value, $key)
    {
        if (in_array($key, ['type', 'fases', 'pole'])) {
            $this->model_equipment[$key] = trim(strtoupper($value));
        }
    }

    // Interacting to Add and remove Equipments in WorkForm
    public function removeEquipment(BtzeroEquipment $equipment)
    {
        $equipment->delete();
        $this->emitSelf('refresh_me');
    }

    public function addEquipment()
    {
        $this->validate();

        $this->workRP->BtzeroEquipment()->updateOrCreate(
            [
                'type' => $this->model_equipment['type'],
                'patrimony' => $this->model_equipment['patrimony'],
            ],
            [
                'fases' => $this->model_equipment['fases'],
                'pole' => $this->model_equipment['pole'],
                'installed' => $this->model_equipment['installed'],
            ]
        );

        $this->emitSelf('refresh_me');



        $this->resetErrorBag();
    }




    public function render()
    {
        return view('livewire.btzero.components.equipaments');
    }
}
