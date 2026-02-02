<?php

namespace App\Http\Livewire\Responsible;

use Livewire\Component;

class Menu extends Component
{
    public ?string $onlySection = null;

    public function mount(): void
    {
        $route = request()->route()?->getName();
        $map = [
            'responsible.validation' => 'analises',
            'responsible.viability' => 'viabilidade',
            'responsible.informes' => 'informes',
            'responsible.parciais' => 'parciais',
            'responsible.d5' => 'd5',
        ];

        $this->onlySection = $map[$route] ?? null;
    }

    public function render()
    {
        return view('livewire.responsible.menu');
    }
}
