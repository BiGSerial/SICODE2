<?php

namespace App\Http\Livewire\Partner\Count;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\WorkReport;
use Livewire\Component;

class Returnworkforms extends Component
{
    use AuthorizesPartnerAccess;

    public function getSumProperty()
    {
        $query = WorkReport::query()->pendingRejectedForPartner();
        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        return $query->count();
    }

    public function render()
    {
        return view('livewire.partner.count.returnworkforms', [
            'sum' => $this->sum
        ]);
    }
}
