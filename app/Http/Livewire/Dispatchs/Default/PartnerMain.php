<?php

namespace App\Http\Livewire\Dispatchs\Default;

use Livewire\{Component, WithPagination};

class PartnerMain extends Component
{
    use \App\Traits\QueuesPartnerExports;
    use Concerns\HasDefaultMainList;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Excel em fila (notifica ao concluir); exporta as notas selecionadas. */
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

        $this->queuePartnerExport('main');
    }

    public function render()
    {
        return $this->renderMainList('livewire.dispatchs.default.partner_main', true);
    }
}
