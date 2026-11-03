<?php

namespace App\Http\Livewire\Dispatchs\Default;

use App\Exports\ExportDDExcel;
use App\Models\Note;
use Livewire\{Component, WithPagination};

class Stack extends Component
{
    use Concerns\HasDefaultControlList;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Exporta as notas das linhas selecionadas. */
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

        $notes = Note::whereHas('Productions', function ($query) {
            return $query->whereIn('id', $this->selected);
        });

        return (new ExportDDExcel())->exportDD($notes->pluck('id')->toArray(), $this->service->service)->download(date('YmdHis-') . 'exportProdNote.xlsx');
    }

    public function render()
    {
        return $this->renderControlList('livewire.dispatchs.default.stack', false);
    }
}
