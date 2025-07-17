<?php

namespace App\Http\Livewire\Protests;

use App\Models\MedProtest;
use Livewire\Component;

class Main extends Component
{
    public function getListProperty()
    {
        return MedProtest::WhereHas('Assignments', function ($q) {
            $q->where('user_id', auth()->id())
            //   ->where('responsible', false)
              ->where('completed', false)
              ->where('transfered', false);
        })->with('Protest', 'Assignments.user', 'Comments.user', 'Notes')->get();
    }

    public function render()
    {
        return view('livewire.protests.main', [
            'list' => $this->list,
        ]);
    }
}
