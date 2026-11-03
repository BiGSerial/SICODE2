<?php

namespace App\Http\Livewire\Dispatchs\Comission;

use Livewire\Component;

/**
 * Lista de controle do Comissionamento (despacho). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 * Toda a lógica está em `Concerns\HasComissionControl` (compartilhada com a parceira).
 */
class Stack extends Component
{
    use Concerns\HasComissionControl;

    public function render()
    {
        return $this->controlView('livewire.dispatchs.comission.stack');
    }
}
