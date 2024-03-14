<?php

namespace App\Http\Livewire\Partner\Forms;

use Livewire\Component;

class Viability extends Component
{
    public $layout = 'layouts.forms.viability';
    public function render()
    {
        return view('livewire.partner.forms.viability')->layout('layouts.forms.viability');

    }
}
