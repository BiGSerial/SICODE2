<?php

namespace App\Http\Livewire\Responsible;

use Livewire\Component;

/**
 * Menu lateral do Responsável: só entrega a rota atual ao `<x-nav.sidebar>`; a estrutura vive em App\Support\Menu\ResponsibleNav
 * (padrão em docs/PADRAO_MENU_LATERAL.md).
 */
class Menu extends Component
{
    public ?string $route = null;

    public function mount(): void
    {
        $this->route = request()->route()?->getName();
    }

    public function render()
    {
        return view('livewire.responsible.menu');
    }
}
