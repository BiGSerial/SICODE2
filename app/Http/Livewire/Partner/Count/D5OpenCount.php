<?php

namespace App\Http\Livewire\Partner\Count;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\FiveNote;
use Livewire\Component;

class D5OpenCount extends Component
{
    use AuthorizesPartnerAccess;

    public bool $returned = false;

    public function render()
    {
        return view('livewire.partner.count.d5-open-count', [
            'count' => $this->count,
        ]);
    }

    public function getCountProperty(): int
    {
        $query = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false)
            ->where('returned', $this->returned);

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToFiveNotes($query);

        return $query->count();
    }
}
