<?php

namespace App\Http\Livewire\Dispatchs\Comission;

use App\Traits\QueuesPartnerExports;
use Livewire\Component;

/**
 * Lista operacional do Comissionamento (parceira). Mesma lista do despacho; a exportação vai para a fila.
 * A view da parceira vive em `comissionamento/` (a de `comission/` não existia e a tela quebrava).
 */
class PartnerMain extends Component
{
    use Concerns\HasComissionList;
    use QueuesPartnerExports;

    public function export_excel()
    {
        $this->queuePartnerExport('main');
    }

    public function render()
    {
        return $this->listView('livewire.dispatchs.comissionamento.partner_main');
    }
}
