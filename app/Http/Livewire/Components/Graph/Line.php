<?php

namespace App\Http\Livewire\Components\Graph;

use Livewire\Component;

class Line extends Component
{
    public $datas = '';

    public $labels = '';

    public $title = '';

    public $Chartid = '';

    public $label = '';

    public function render()
    {
        return view('livewire.components.graph.line');
    }
}
