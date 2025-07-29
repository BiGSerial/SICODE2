<?php

namespace App\Http\Livewire\Components\Count\Protest;

use App\Models\MedProtest;
use Livewire\Component;

class HasProtests extends Component
{
    public function getHasProtestsProperty()
    {
        return MedProtest::whereHas('assignments', function ($query) {
            $query
                ->where('user_id', auth()->id())
                ->where('completed', false)
                ->where('responsible', false)
                ->where('transfered', false);
        })->exists();
    }

    public function render()
    {
        return view('livewire.components.count.protest.has-protests', [
            'hasProtests' => $this->hasProtests,
        ]);
    }
}
