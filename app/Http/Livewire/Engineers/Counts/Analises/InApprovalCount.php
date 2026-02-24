<?php

namespace App\Http\Livewire\Engineers\Counts\Analises;

use App\Models\Note;
use Livewire\Component;

class InApprovalCount extends Component
{
    public $engineer;

    public function mount($engineer = false)
    {
        $this->engineer = $engineer;
    }

    public function getCountProperty()
    {
        $query = Note::query();

        $query->whereHas('Approval', function ($q) {
            $q->where('approved', false);
            if (!$this->engineer) {
                $q->whereIn('user_id', auth()->user()->visibleUserIdsForWork());
            }
        });



        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.analises.in-approval-count', [
            'count' => $this->count
        ]);
    }
}
