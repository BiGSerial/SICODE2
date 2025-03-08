<?php

namespace App\Http\Livewire\Engineers\Counts\Analises;

use App\Models\Note;
use Livewire\Component;

class InApprovalReturn extends Component
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
                $q->where('user_id', auth()->id());
            }
            $q->whereHas('Reclaims', function ($query) {
                $query->orderBy('reclaims.id', 'desc') // ESPECIFICA A TABELA
                      ->limit(1)
                      ->where('completed', true);
            });
        });



        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.analises.in-approval-return', [
            'count' => $this->count
        ]);
    }
}
