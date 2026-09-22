<?php

namespace App\Http\Livewire\Partner\Count;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Note;
use Livewire\Component;

class Hiredviability extends Component
{
    use AuthorizesPartnerAccess;

    public function getCountProperty()
    {
        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);

            $this->applyPartnerCompanyScope($q);

        })
            ->with(['Viabilities' => function ($query) {
                $query->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);
            }, 'Files']);

        $this->applyPartnerBranchScopeToNotes($query);

        $this->emit('hiredcount', $query->count());

        return $query->count();

    }


    public function render()
    {
        return view('livewire.partner.count.hiredviability', [
            'count' => $this->count
        ]);
    }
}
