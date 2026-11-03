<?php

namespace App\Http\Livewire\Dispatchs\Default;

use App\Exports\ExportDDExcel;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use Concerns\HasDefaultMainList;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

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
        return $this->renderMainList('livewire.dispatchs.default.main', false);
    }
}
