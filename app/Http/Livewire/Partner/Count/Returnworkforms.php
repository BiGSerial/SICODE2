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
        $query = WorkReport::when(!Auth()->User()->superadm, function ($q) {
            $q->where(function ($query) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
            });
        })
        ->pendingRejectedForPartner();

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
