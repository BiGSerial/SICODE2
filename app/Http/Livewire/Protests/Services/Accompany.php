<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\MedProtest;
use Livewire\Component;

class Accompany extends Component
{
    public function getListProperty()
    {
        return MedProtest::WhereHas('Assignments', function ($q) {
            $q->where('user_id', auth()->id())
                ->where('monitoring', true)
                ->where('completed', false);
        })->with('Protest', 'Assignments.user', 'Comments.user', 'Notes')->get();
    }

    public function render()
    {
        return view('livewire.protests.services.accompany', [
            'list' => $this->list,
        ]);
    }
}
