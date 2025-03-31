<?php

namespace App\Http\Livewire\Partner\Count;

use App\Models\Note;
use Livewire\Component;

class Hiredviability extends Component
{
    public function getCountProperty()
    {
        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);

            if (!Auth()->User()->superadm) {

                $q->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            }

        })
            ->with(['Viabilities' => function ($query) {
                $query->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);
            }, 'Files']);


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
