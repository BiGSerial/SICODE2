<?php

namespace App\Http\Livewire\Engineer;

use App\Models\Note;
use Livewire\Component;

class Main extends Component
{
    protected $listeners = [
        'update_list' => '$refresh'
    ];




    public function getListsProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            $q->where('approved', false)
                ->where('engineer_id', Auth()->User()->id)
                ->where('tacit', false)
                ->where('canceled', false)
                ->where('rejected', true)
                ->where('completed', false);
        })
            ->with(['Viabilities' => function ($query) {
                $query->where('approved', false)
                ->where('engineer_id', Auth()->User()->id)
                ->where('tacit', false)
                ->where('canceled', false)
                ->where('rejected', true)
                ->where('completed', false)
                ->with('Company', 'User', 'Form', 'Reclaims');
            }, 'Files'])->paginate(50);
    }

    public function render()
    {
        return view('livewire.engineer.main', [
            'lists' => $this->lists
        ]);
    }
}
