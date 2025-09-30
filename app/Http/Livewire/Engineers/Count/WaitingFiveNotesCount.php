<?php

namespace App\Http\Livewire\Engineers\Count;

use App\Models\FiveNote;
use Livewire\Component;

class WaitingFiveNotesCount extends Component
{
    public function getCountProperty()
    {
        $base = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false);

        if (!auth()->user()->superadm) {
            $base->where(function ($q) {
                $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                ->orWhere('company_id', Auth()->user()->Company->id);
            });
        }       

        return $base->count();
    }

    public function render()
    {
        return view('livewire.engineers.count.waiting-five-notes-count', [
            'count' => $this->count
        ]);
    }
}
