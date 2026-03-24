<?php

namespace App\Http\Livewire\Responsible;

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
            in_array($route, [
                'responsible.validation',
                'responsible.approve_list',
                'responsible.approve_control',
                'responsible.approve_hist',
            ], true) => 'analises',

            in_array($route, [
                'responsible.main',
                'responsible.viability',
                'responsible.viability_waiting',
                'responsible.viab_list',
                'responsible.rejecte_viab',
                'responsible.intern_return',
                'responsible.justified_viab',
                'responsible.viab_hist',
            ], true) => 'viabilidade',

            in_array($route, [
                'responsible.informes',
                'responsible.inform_obra',
                'responsible.inform_list',
                'responsible.ads.requests',
            ], true) => 'informes',

            in_array($route, [
                'responsible.parciais',
                'responsible.partial_hist',
            ], true) => 'parciais',

            $route === 'responsible.d5',
            $route === 'responsible.dfive.waiting' => 'd5',

            default => null,
        };
    }

    public function render()
    {
        return view('livewire.responsible.menu');
    }
}
