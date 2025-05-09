<?php

namespace App\Http\Livewire\Services\Oexterno\Protocols;

use App\Models\Note;
use Livewire\Component;

class Main extends Component
{
    public ?Note $note = null;

    public function mount()
    {
        $this->note = Note::where('note', request()->route('note'))->with('externals.protocols', 'externals.comments', 'externals.user')->first();

        if (!$this->note) {
            abort(404, 'Página não encontrada');
        }
    }


    public function render()
    {
        return view('livewire.services.oexterno.protocols.main');
    }
}
