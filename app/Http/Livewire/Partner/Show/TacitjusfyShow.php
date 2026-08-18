<?php

namespace App\Http\Livewire\Partner\Show;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TacitjusfyShow extends Component
{
    use AuthorizesPartnerAccess;

    public $viability;
    public $description;
    public $hasFile = false;


    protected $listeners = [
        'getTacitInfo',
    ];

    public function hasFile($value)
    {
        $this->hasFile = $value;
    }

    public function getTacitInfo(Viability $viability)
    {
        $this->authorizePartnerAccess('viability.tacit');

        $query = Viability::query()->whereKey($viability->id);
        $this->applyPartnerCompanyScope($query);
        $this->applyPartnerBranchScopeToNoteRelation($query);

        $this->viability = $query->firstOrFail();

        if ($this->viability) {

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'tacitresponse-show-modal',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.partner.show.tacitjusfy-show');
    }
}
