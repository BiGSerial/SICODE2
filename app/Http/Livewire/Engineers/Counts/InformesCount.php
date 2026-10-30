<?php

namespace App\Http\Livewire\Engineers\Counts;

use Livewire\Component;

/**
 * Contador do item "INFORMES" do menu superior: parciais aguardando aprovação + informes sem saldo no PEP.
 */
class InformesCount extends Component
{
    public function getCountProperty(): int
    {
        return (new CountParcial())->getCountProperty() + (new PepAlertsCount())->getCountProperty();
    }

    public function render()
    {
        return view('livewire.engineers.counts.informes-count', ['count' => $this->count]);
    }
}
