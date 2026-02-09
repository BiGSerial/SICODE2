<?php

namespace App\Http\Livewire\Logger;

use App\Models\UpdateExecutionLog;
use Livewire\Component;

class Updatelogs extends Component
{
    public function getListsProperty()
    {
        return UpdateExecutionLog::query()
            ->orderByDesc('date_inicio')
            ->limit(200)
            ->get();
    }

    public function render()
    {
        return view('livewire.logger.updatelogs', [
            'lists' => $this->lists,
        ]);
    }
}
