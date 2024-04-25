<?php

namespace App\Http\Livewire\Partner\Count;

use Livewire\Component;

class Sumviability extends Component
{
    public int $hiredCount = 0;
    public int $todoCount = 0;

    protected $listeners = [
        'todocount',
        'hiredcount',
    ];

    public function hiredcount($count)
    {
        $this->hiredCount = $count;
    }

    public function todocount($count)
    {
        $this->todoCount = $count;
    }

    public function getSumcountProperty()
    {
        return $this->hiredCount + $this->todoCount;
    }

    public function render()
    {
        return view('livewire.partner.count.sumviability', [
            'sum' => $this->sumcount
        ]);
    }
}
