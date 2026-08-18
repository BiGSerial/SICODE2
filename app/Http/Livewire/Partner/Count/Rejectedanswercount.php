<?php

namespace App\Http\Livewire\Partner\Count;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Viability;
use Livewire\Component;

class Rejectedanswercount extends Component
{
    use AuthorizesPartnerAccess;

    public function getCountProperty()
    {
        $query = Viability::query()->where('rejected', true)
                ->where('completed', false)
                ->where('status', 5);

        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }

        $this->applyPartnerBranchScopeToNoteRelation($query);

        return $query->count();

    }


    public function render()
    {
        return view('livewire.partner.count.rejectedanswercount', [
            'count' => $this->count
        ]);
    }
}
