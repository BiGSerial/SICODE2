<?php

namespace App\Http\Livewire\Partner;

use Livewire\Component;

/**
 * Menu lateral do portal da parceira: só entrega a rota atual ao `<x-nav.sidebar>`; a estrutura e as permissões vivem em
 * App\Support\Menu\PartnerNav (padrão em docs/PADRAO_MENU_LATERAL.md, tema `snav--partner`).
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
        return view('livewire.partner.menu');
    }
}
