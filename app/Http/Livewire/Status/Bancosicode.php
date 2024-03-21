<?php

namespace App\Http\Livewire\Status;

use App\Models\Bancoupdate;
use Livewire\Component;

class Bancosicode extends Component
{
    public function getLogProperty()
    {
        return Bancoupdate::orderBy('created_at', 'DESC')->first();
    }

    public function render()
    {
        return view('livewire.status.bancosicode', [
            'bdupdate' => $this->log,
        ]);
    }
}
