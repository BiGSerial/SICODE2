<?php

namespace App\Http\Livewire\Components\Graph;

use Livewire\Component;

class Stackbar extends Component
{
    public $datasets = '';

    public $labels = '';

    public $title = '';

    public $Chartid = '';

    public $label = '';

    protected $listeners = [
        'updateSelfGraph' => '$refresh',
        'updateGraph'     => 'updateGraph',
    ];

    public function updateGraph()
    {
        $this->emit('updateSelfGraph');
        $this->emit('end_update');
    }

    public function render()
    {
        return view('livewire.components.graph.stackbar');
    }
}
