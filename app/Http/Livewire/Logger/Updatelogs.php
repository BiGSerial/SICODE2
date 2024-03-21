<?php

namespace App\Http\Livewire\Logger;

use App\Custom\RegistroJson;
use Livewire\Component;

class Updatelogs extends Component
{
    public function getListsProperty()
    {
        $json = new RegistroJson(base_path('registroUpdate.json'));

        return $json->orderBy('date', 'DESC')
            ->get();

    }

    public function render()
    {
        return view('livewire.logger.updatelogs', [
            'lists' => $this->lists,
        ]);
    }
}
