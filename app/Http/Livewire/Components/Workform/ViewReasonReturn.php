<?php

namespace App\Http\Livewire\Components\Workform;

use App\Http\Livewire\Partner\Actions\WorkedReturnForm;
use App\Models\WorkReport;
use Livewire\Component;

class ViewReasonReturn extends Component
{
    public ?WorkReport $workReport = null;
    public $pag = 0;

    protected $listeners = [
        'workReturnViews'
    ];

    // Reasons Navigate if More than one.
    public function getPage()
    {
        if ($this->workReport->Returnwork) {
            $total = $this->workReport->Returnwork->count();

            return $this->pag = $total - 1;

        }
    }

    public function nextPage()
    {
        if (($this->workReport->Returnwork->count() - 1) > $this->pag) {
            return $this->pag++;
        }
    }

    public function previousPage()
    {
        if ($this->pag == 0) {
            return;
        }

        return --$this->pag;
    }

    public function workReturnViews(WorkReport $workReport)
    {
        $this->workReport = $workReport;


        if ($this->workReport) {

            $this->getPage();

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'workRejectedViewCategory',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.components.workform.view-reason-return');
    }
}
