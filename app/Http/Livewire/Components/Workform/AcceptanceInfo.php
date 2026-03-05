<?php

namespace App\Http\Livewire\Components\Workform;

use App\Models\WorkReport;
use Livewire\Component;

class AcceptanceInfo extends Component
{
    public ?WorkReport $workReport = null;

    protected $listeners = [
        'openAcceptanceInfo',
    ];

    public function openAcceptanceInfo(WorkReport $workReport)
    {
        $this->workReport = WorkReport::query()
            ->with([
                'Note:id,note',
                'Company:id,name',
                'User:id,name,email',
            ])
            ->find($workReport->id);

        if (!$this->workReport) {
            return;
        }

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'workAcceptanceInfoModal',
        ]);
    }

    public function render()
    {
        return view('livewire.components.workform.acceptance-info');
    }
}
