<?php

namespace App\Http\Livewire\Partner\Count;

use App\Models\Note;
use Livewire\Component;

class Todoviabilitycount extends Component
{
    public function getCountProperty()
    {
        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', false)
                ->where('completed', false);

            if (!Auth()->User()->superadm) {

                if (isset(Auth()->User()->Employee->Contract->Company->id)) {
                    $q->where('company_id', Auth()->User()->Employee->Contract->company->id);
                } else {
                    $q->where('company_id', null);
                }
            }

        })
            ->with(['Viabilities' => function ($query) {
                $query->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', false)
                ->where('completed', false);
            }, 'Files']);

        $this->emit('todocount', $query->count());

        return $query->count();

    }


    public function render()
    {
        return view('livewire.partner.count.todoviabilitycount', [
            'count' => $this->count
        ]);
    }
}
