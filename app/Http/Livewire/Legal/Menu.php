<?php

namespace App\Http\Livewire\Legal;

use Livewire\Component;

class Menu extends Component
{
    public string $activeSection = '';

    public function render()
    {
        return view('livewire.legal.menu');
    }
}
