<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Note;
use Livewire\Component;

class Histhiring extends Component
{
    protected $listeners = [
        'update_list' => '$refresh'
    ];

    public function getListsProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            $q->where('user_id', auth()->user()->id)
                ->where('hired', true);
        })
            ->with(['Viabilities' => function ($query) {
                $query->where('hired', true)
                ->with('Company', 'User', 'Form', 'Comments.User');
            }, 'Files'])->paginate(50);
    }



    public function render()
    {
        return view('livewire.construction.hiring.histhiring', [
            'lists' => $this->lists
        ]);
    }
}
