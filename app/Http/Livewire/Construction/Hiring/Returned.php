<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Note;
use Livewire\Component;

class Returned extends Component
{
    protected $listeners = [
        'update_list' => '$refresh'
    ];

    public function getListsProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            $q->where('engineer', true)
                ->where('completed', false);
        })
            ->with(['Viabilities' => function ($query) {
                $query->where('engineer', true)
                ->where('completed', false)
                ->with('Company', 'User', 'Form', 'Comments.User');
            }, 'Files'])->paginate(50);
    }


    public function render()
    {
        return view('livewire.construction.hiring.returned', [
            'lists' => $this->lists
        ]);
    }
}
