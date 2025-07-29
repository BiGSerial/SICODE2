<?php

namespace App\Http\Livewire\Components\Count\Protest;

use App\Models\MedProtest;
use Livewire\Component;

class CountProtests extends Component
{
    public $type;

    public function mount($type = null)
    {
        $this->type = mb_strtoupper($type ?? '');
    }

    public function getCountProperty()
    {
        $query = MedProtest::query()
            ->whereHas('assignments', function ($query) {
                $query->where('completed', false)
                    ->where('responsible', false)
                    ->where('user_id', auth()->id());

                if ($this->type == 'M') {
                    $query->where('monitoring', true);
                }

                if ($this->type == 'U') {
                    $query->where('user', true);
                }
            });

        return $query->count();
    }

    public function render()
    {
        return view('livewire.components.count.protest.count-protests', [
            'count' => $this->count
        ]);
    }
}
