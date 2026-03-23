<?php

namespace App\Http\Livewire\Engineers;

use Livewire\Component;

class Menu extends Component
{
    public ?string $onlySection = null;

    public function mount(): void
    {
        $route = request()->route()?->getName();
        if (!$route) {
            $this->onlySection = null;
            return;
        }

        $this->onlySection = match (true) {
            $route === 'engineers.validation',
            str_starts_with($route, 'engineers.analises.') => 'analises',

            in_array($route, [
                'engineers.main',
                'engineers.viability',
                'engineers.viability_waiting',
                'engineers.viab_list',
                'engineers.rejecte_viab',
                'engineers.intern_return',
                'engineers.justified_viab',
                'engineers.viab_hist',
                'engineers.viabilityreports',
            ], true) => 'viabilidade',

            in_array($route, [
                'engineers.informes',
                'engineers.inform_obra',
                'engineers.inform_list',
                'engineers.ads.requests',
                'engineers.ads.situation',
                'engineers.dashboard.conclusion_inform',
            ], true) => 'informes',

            in_array($route, [
                'engineers.parciais',
                'engineers.info.parcial',
                'engineers.hist.parcial',
            ], true) => 'parciais',

            $route === 'engineers.d5',
            $route === 'engineers.dfive.waiting' => 'd5',

            str_starts_with($route, 'engineers.cancellations.') => 'cancellations',

            default => null,
        };
    }

    public function render()
    {
        return view('livewire.engineers.menu');
    }
}
