<?php

namespace App\Http\Livewire\Dispatchs\Comission;

use App\Exports\ExportDDExcel;
use Livewire\Component;

/**
 * Lista operacional do Comissionamento (despacho). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 * Filtros, consulta, seleção e atribuição de DD em `Concerns\HasComissionList` (compartilhado com a parceira).
 */
class Main extends Component
{
    use Concerns\HasComissionList;

    /** Exporta as notas SELECIONADAS (sem seleção, avisa e não exporta). */
    public function export_excel()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para Exportar!',
                'timer'    => 2500,
            ]);

            return;
        }

        return (new ExportDDExcel())->exportDD($this->selected, $this->service->service)->download(date('YmdHis-') . 'exportDD.xlsx');
    }

    public function render()
    {
        return $this->listView('livewire.dispatchs.comission.main');
    }
}
