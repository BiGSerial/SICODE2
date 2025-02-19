<?php

namespace App\Http\Livewire\Components\Ramalform;

use App\Http\Livewire\Partner\Actions\WorkedReturnForm;
use App\Models\RamalReport;
use Livewire\Component;

class ViewReasonReturn extends Component
{
    public ?RamalReport $workReport = null;
    public $pag = 0;

    protected $listeners = [
        'ramalReturnViews'
    ];

    // Reasons Navigate if More than one.
    public function getPage()
    {
        if ($this->workReport->ReturnRamal) {
            $total = $this->workReport->ReturnRamal->count();

            return $this->pag = $total - 1;

        }
    }

    public function nextPage()
    {
        if (($this->workReport->ReturnRamal->count() - 1) > $this->pag) {
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

    public function ramalReturnViews(RamalReport $workReport)
    {
        $this->workReport = $workReport;


        if ($this->workReport) {

            $this->getPage();

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'ramalRejectedViewCategory',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.components.ramalform.view-reason-return');
    }
}
