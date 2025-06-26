<?php

namespace App\Http\Livewire\Services\Oexterno\Actions\Protest;

use App\Models\MedProtest;
use App\Models\Service;
use Livewire\Component;

class ControlMedProtest extends Component
{
    public $modProtest;
    public $notePage = 0;
    public $needsEvidence = 0;
    public $needsConfirmation = 0;

    public $serviceList = [];

    protected $listeners = [
        'openModProtestControl',
        'refreshComponent' => '$refresh',
    ];

    public function mount()
    {
        $this->serviceList = Service::orderBy('service')->get();
    }

    public function nextPage($noteList)
    {
        if ($this->notePage < count($noteList) - 1) {
            $this->notePage++;
        }
    }

    public function previousPage()
    {
        if ($this->notePage > 0) {
            $this->notePage--;
        }
    }

    public function openModProtestControl(MedProtest $modProtest)
    {
        $this->modProtest = $modProtest->load('protest');

        if ($this->modProtest) {

            $this->notePage = 0;


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
