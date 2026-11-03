<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Service;
use Livewire\Component;

/**
 * Menu lateral da Contratação: entrega a atividade e a rota atual ao `<x-nav.sidebar>`; a estrutura vive em App\Support\Menu\HiringNav
 * (padrão em docs/PADRAO_MENU_LATERAL.md).
 */
class Menu extends Component
{
    public $service;

    public ?string $route = null;

    public function mount($service): void
    {
        $this->service = Service::where('uuid', $service)->first();
        $this->route   = request()->route()?->getName();
    }

    public function render()
    {
        return view('livewire.construction.hiring.menu');
    }
}
