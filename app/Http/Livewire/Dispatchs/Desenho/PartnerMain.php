<?php

namespace App\Http\Livewire\Dispatchs\Desenho;

/**
 * Lista para Desenho da parceira: mesma tela, mesmos filtros e mesma consulta da `Main` (a visibilidade por empresa já
 * está na consulta); só a exportação muda (fila `ExportPartnerDispatchJob`, que relê o estado público do componente).
 */
class PartnerMain extends Main
{
    use \App\Traits\QueuesPartnerExports;

    public function export_excel()
    {
        $this->queuePartnerExport('main');
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.desenho.partner_main';
    }
}
