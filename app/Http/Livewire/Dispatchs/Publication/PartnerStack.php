<?php

namespace App\Http\Livewire\Dispatchs\Publication;

/**
 * Despacho da parceira → Controle de Publicação. Mesma lista de controle de `Stack`; muda só a exportação (em fila) e a view.
 */
class PartnerStack extends Stack
{
    use \App\Traits\QueuesPartnerExports;

    public function export_excel()
    {
        $this->queuePartnerExport('stack');
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.publication.partner_stack';
    }

    protected function mainRouteName(): string
    {
        return 'dispatch.partner';
    }
}
