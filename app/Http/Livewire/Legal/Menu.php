<?php

namespace App\Http\Livewire\Legal;

use Livewire\Component;

/**
 * Menu lateral do Jurídico: só entrega a rota atual ao `<x-nav.sidebar>`; a estrutura vive em App\Support\Menu\LegalNav
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
        return view('livewire.legal.menu');
    }
}
