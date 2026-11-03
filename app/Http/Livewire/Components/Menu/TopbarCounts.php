<?php

namespace App\Http\Livewire\Components\Menu;

use App\Services\Menu\TopbarCounts as Counts;
use Livewire\Component;

/**
 * Único componente Livewire do menu superior: calcula os contadores de todos os itens de uma vez e os entrega ao navegador por evento
 * (`topbar:counts`). Carrega depois da página (`wire:init`) e atualiza a cada 2 minutos.
 */
class TopbarCounts extends Component
{
    public function load(): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        $this->dispatchBrowserEvent('topbar:counts', ['counts' => app(Counts::class)->for($user)]);
    }

    public function render()
    {
        return <<<'HTML'
            <div wire:init="load" wire:poll.120s="load" class="d-none" aria-hidden="true"></div>
        HTML;
    }
}
