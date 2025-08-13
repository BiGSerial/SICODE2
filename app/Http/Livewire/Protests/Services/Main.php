<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\MedProtest;
use Livewire\Component;

class Main extends Component
{
    public function getListProperty()
    {
        return MedProtest::WhereHas('assignments', function ($q) {
            $q->where('user_id', auth()->id())
                ->where('user', true)
                ->where('completed', false);
        })->with('Protest', 'Assignments.user', 'Comments.user', 'Notes')->get();
    }

    public function render()
    {
        return view('livewire.protests.services.main', [
            'list' => $this->list,
        ]);
    }
}
