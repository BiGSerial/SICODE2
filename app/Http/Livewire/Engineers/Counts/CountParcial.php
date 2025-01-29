<?php

namespace App\Http\Livewire\Engineers\Counts;

use App\Models\Partial;
use Livewire\Component;

class CountParcial extends Component
{
    public $menu;

    public function mount(bool $menu = false)
    {
        $this->menu = $menu;
    }


    public function getCountProperty()
    {
        $query = Partial::query();

        $query->where(function ($q) {
            $q->where('allow', false)
                ->Where('deny', false);
        });

        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty() && Auth()->user()->engineer) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.count-parcial', [
            'count' => $this->count
        ]);
    }
}
