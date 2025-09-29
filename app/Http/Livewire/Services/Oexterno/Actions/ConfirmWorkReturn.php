<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\Reclaim;
use Livewire\Component;

class ConfirmWorkReturn extends Component
{
    public $reclaim;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openConfirmWorkReturn',
    ];


    public function openConfirmWorkReturn(Reclaim $reclaim)
    {
        $this->reclaim = $reclaim->load('externals', 'externals.entity', 'note', 'service');

        if ($this->reclaim) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modalApproveReclaim',
            ]);
        }
    }

    public function getItemProperty()
    {
        return $this->reclaim;
    }

    // TODO: Implementar a confirmação do retorno do serviço.
    public function render()
    {
        return view('livewire.services.oexterno.actions.confirm-work-return', [
            'item' => $this->item,
        ]);
    }
}
