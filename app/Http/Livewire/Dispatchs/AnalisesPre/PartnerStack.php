<?php

namespace App\Http\Livewire\Dispatchs\AnalisesPre;

/**
 * Controle da parceira para Análises Pré: a mesma tela e a mesma consulta do `Stack`; só a exportação muda (vai para a
 * fila, lendo o estado e os filtros desta tela).
 */
class PartnerStack extends Stack
{
    use \App\Traits\QueuesPartnerExports;

    public function export_excel()
    {
        $this->queuePartnerExport('stack');
    }
}
