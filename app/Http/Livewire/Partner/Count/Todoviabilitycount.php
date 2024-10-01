<?php

namespace App\Http\Livewire\Partner\Count;

use App\Models\Note;
use App\Models\Viability;
use Livewire\Component;

class Todoviabilitycount extends Component
{
    public function getCountProperty()
    {
        $query = Viability::query()
                ->where('completed', false)
                ->where('status', 1);

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

        return $query->count();

    }


    public function render()
    {
        return view('livewire.partner.count.todoviabilitycount', [
            'count' => $this->count
        ]);
    }
}
