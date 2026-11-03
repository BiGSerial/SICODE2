<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

/**
 * Controle de Fiscalização da parceira: mesma consulta e mesmo desenho do `Stack` (uma regra só para as duas telas);
 * muda a visão e o atalho para a lista da parceira.
 */
class PartnerStack extends Stack
{
    protected bool $partner = true;

    protected function viewName(): string
    {
        return 'livewire.dispatchs.fiscalizacao.partner_stack';
    }
}
