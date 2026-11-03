<?php

namespace App\Http\Livewire\Btzero;

use Livewire\Component;

/**
 * Menu lateral do BT Zero: só entrega a rota atual ao `<x-nav.sidebar>`; a estrutura vive em App\Support\Menu\BtzeroNav
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
        return view('livewire.btzero.menu');
    }
}
