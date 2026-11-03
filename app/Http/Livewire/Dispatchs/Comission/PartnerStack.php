<?php

namespace App\Http\Livewire\Dispatchs\Comission;

use Livewire\Component;

/**
 * Lista de controle do Comissionamento (parceira). Mesma lista do despacho.
 * A view da parceira vive em `comissionamento/` (a de `comission/` não existia e a tela quebrava).
 */
class PartnerStack extends Component
{
    use Concerns\HasComissionControl;

    public function render()
    {
        return $this->controlView('livewire.dispatchs.comissionamento.partner_stack');
    }
}
