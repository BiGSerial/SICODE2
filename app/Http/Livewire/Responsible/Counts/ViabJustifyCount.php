<?php

namespace App\Http\Livewire\Responsible\Counts;

use App\Models\Viability;
use Livewire\Component;

class ViabJustifyCount extends Component
{
    public function getCountProperty()
    {
        $query = Viability::query();

        $query->join('tacit_comments', 'viabilities.id', '=', 'tacit_comments.viability_id')
            ->where('tacit_comments.granted', false)
            ->where('tacit_comments.dismissed', false)
            ->where('viabilities.tacit', true)
            ->orderBy('tacit_comments.justified_at', 'desc');

        return $query->select('viabilities.*', 'tacit_comments.justified_at as comment_justified_at')->count();
    }

    public function render()
    {
        return view('livewire.responsible.counts.viab-justify-count', [
            'count' => $this->count
        ]);
    }
}
