<?php

namespace App\Http\Livewire\Dispatchs\Analises;

/**
 * Lista de Análises da parceira: mesma lista operacional do Despacho (consulta, filtros e linhas vêm de `Main`), com a
 * exportação em fila e o perfil `partner` (coluna Dt Status, sem o filtro Material nem o atalho MMGD).
 */
class PartnerMain extends Main
{
    use \App\Traits\QueuesPartnerExports;

    protected function listProfile(): string
    {
        return 'partner';
    }

    protected function hasMaterialFilter(): bool
    {
        return false;
    }

    public function export_excel()
    {
        $this->queuePartnerExport('main');
    }
}
