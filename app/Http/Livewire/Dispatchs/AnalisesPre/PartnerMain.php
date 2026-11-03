<?php

namespace App\Http\Livewire\Dispatchs\AnalisesPre;

/**
 * Lista da parceira para Análises Pré: a mesma tela e a mesma consulta do `Main`; só a exportação muda (vai para a fila,
 * lendo o estado e os filtros desta tela).
 */
class PartnerMain extends Main
{
    use \App\Traits\QueuesPartnerExports;

    public function export_excel()
    {
        $this->queuePartnerExport('main');
    }
}
