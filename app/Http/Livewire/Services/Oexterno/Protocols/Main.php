<?php

namespace App\Http\Livewire\Services\Oexterno\Protocols;

use App\Models\Note;
use App\Models\Protocol;
use Livewire\Component;

class Main extends Component
{
    public ?Note $note = null;
    public $openExternalId;
    public $openExternalContactId;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'setOpenExternal',
        'setOpenExternalContact',
    ];

    public function mount()
    {
        $this->note = Note::where('note', request()->route('note'))->with('externals.protocols', 'externals.comments', 'externals.user')->first();

        if (!$this->note) {
            abort(404, 'Página não encontrada');
        }
    }

    public function setOpenExternal($id)
    {

        $this->openExternalId = $id;
    }

    public function setOpenExternalContact($id)
    {

        $this->openExternalContactId = $id;
    }

    public function deleteProtocol(Protocol $protocol)
    {

        if ($protocol) {
            $protocol->delete();
        }


        $this->emitself('refreshComponent');
    }


    public function render()
    {
        return view('livewire.services.oexterno.protocols.main');
    }
}
