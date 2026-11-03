<?php

namespace App\Http\Livewire\Dispatchs\Analises;

/**
 * Controle de Análises da parceira: mesmo controle de `Stack` (consulta, filtros, linhas e ações), com a exportação em fila.
 */
class PartnerStack extends Stack
{
    use \App\Traits\QueuesPartnerExports;

    public function export_excel()
    {
        $this->queuePartnerExport('stack');
    }
}
