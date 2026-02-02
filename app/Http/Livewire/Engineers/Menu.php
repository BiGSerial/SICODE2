<?php

namespace App\Http\Livewire\Engineers;

use Livewire\Component;

class Menu extends Component
{
    public ?string $onlySection = null;

    public function mount(): void
    {
        $route = request()->route()?->getName();
        $map = [
            'engineers.validation' => 'analises',
            'engineers.viability' => 'viabilidade',
            'engineers.informes' => 'informes',
            'engineers.parciais' => 'parciais',
            'engineers.d5' => 'd5',
        ];

        $this->onlySection = $map[$route] ?? null;
    }

    public function render()
    {
        return view('livewire.engineers.menu');
    }
}
