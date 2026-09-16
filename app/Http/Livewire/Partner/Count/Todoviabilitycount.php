<?php

namespace App\Http\Livewire\Partner\Count;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Viability;
use Livewire\Component;

class Todoviabilitycount extends Component
{
    use AuthorizesPartnerAccess;

    public function getCountProperty()
    {
        $query = Viability::query()
                ->where('completed', false)
                ->where('status', 1);

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        return $query->count();

    }


    public function render()
    {
        return view('livewire.partner.count.todoviabilitycount', [
            'count' => $this->count
        ]);
    }
}
