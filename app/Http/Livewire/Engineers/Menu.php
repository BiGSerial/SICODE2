<?php

namespace App\Http\Livewire\Engineers;

use Livewire\Component;

/**
 * Menu lateral da Engenharia: só entrega a rota atual ao `<x-nav.sidebar>`; a estrutura vive em App\Support\Menu\EngineerNav
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
        return view('livewire.engineers.menu');
    }
}
