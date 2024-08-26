<?php

namespace App\Http\Livewire\Components\Status;

use App\Models\Production;
use App\Models\Notetimeline;
use Livewire\Component;

class ShowStatus extends Component
{
    public ?Production $production = null;
    public ?Notetimeline $status = null;

    protected $listeners = [
        'showStatus'
    ];

    public function showStatus(Production $production, $status)
    {

        $this->production = $production;

        if ($this->status = $this->production->Note->Historic->Where('status', $status)->where('user_id', $this->production->user_id)->where('service_id', $this->production->service_id)->last()) {

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'statusView',
            ]);

        } else {
            $this->status = null;

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Sem infomação de Status',
                'timer'    => 4000,
            ]);

            return;
        }
    }

    public function render()
    {
        return view('livewire.components.status.show-status');
    }
}
