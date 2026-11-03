<?php

namespace App\Http\Livewire\Dispatchs\Desenho;

/**
 * Controle de Desenho da parceira: mesma tela, mesmos filtros e mesma consulta do `Stack` (a visibilidade por empresa já
 * está em `controlQuery()`); só a exportação muda (fila `ExportPartnerDispatchJob`, que relê o estado público do componente).
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
        return 'livewire.dispatchs.desenho.partner_stack';
    }
}
