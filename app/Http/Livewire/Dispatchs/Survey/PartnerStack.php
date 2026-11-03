<?php

namespace App\Http\Livewire\Dispatchs\Survey;

/** Controle do Levantamento da parceira: a mesma lista de `Stack` (mesma consulta, filtros e exportação). */
class PartnerStack extends Stack
{
    protected function viewName(): string
    {
        return 'livewire.dispatchs.levantamento.partner_stack';
    }
}
