<?php

namespace App\Http\Livewire\Services\Oexterno\Counts;

use App\Models\External;
use Livewire\Component;

class CountStatus extends Component
{
    public $stats;
    public $null = false;

    public function mount(array $values = [], $null = false)
    {

        $this->stats = $values;
        $this->null = $null;
    }


    public function getCountProperty()
    {
        // dd($this->stats);

        $q = External::query()->where('completed', false);

        if ($this->null) {
            $q->where(function ($qq) {
                $qq->whereIn('status', $this->stats)
                   ->orWhereNull('status');
            });
        } else {
            $q->whereIn('status', $this->stats);
        }

        return $q;

    }


    public function render()
    {



        return view('livewire.services.oexterno.counts.count-status', [
            'count' => $this->count->count(),
        ]);
    }
}
