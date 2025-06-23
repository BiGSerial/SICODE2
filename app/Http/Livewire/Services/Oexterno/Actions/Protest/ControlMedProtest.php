<?php

namespace App\Http\Livewire\Services\Oexterno\Actions\Protest;

use App\Models\MedProtest;
use Livewire\Component;

class ControlMedProtest extends Component
{
    public $modProtest;

    protected $listeners = [
        'openModProtestControl',
        'refreshComponent' => '$refresh',
    ];

    public function openModProtestControl(MedProtest $modProtest)
    {
        $this->modProtest = $modProtest->load('protest');

        if ($this->modProtest) {
            $this->dispatchBrowserEvent('showModal', [
               'id' => 'controlModProtestModal',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.services.oexterno.actions.protest.control-med-protest');
    }
}
