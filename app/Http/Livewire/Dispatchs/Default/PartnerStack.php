<?php

namespace App\Http\Livewire\Dispatchs\Default;

use App\Models\Production;
use Livewire\{Component, WithPagination};

class PartnerStack extends Component
{
    use \App\Traits\QueuesPartnerExports;
    use Concerns\HasDefaultControlList;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /**
     * Excel em fila (notifica ao concluir). O Job da parceira lê `selected` como ids de nota para esta atividade; no controle
     * a seleção são ids de Production, então eles são trocados pelos das notas só durante o envio.
     */
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

        $productionIds  = $this->selected;
        $this->selected = Production::whereIn('id', $productionIds)->pluck('note_id')->all();

        $this->queuePartnerExport('stack');

        $this->selected = $productionIds;
    }

    public function render()
    {
        return $this->renderControlList('livewire.dispatchs.default.partner_stack', true);
    }
}
