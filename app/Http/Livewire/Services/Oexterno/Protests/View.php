<?php

namespace App\Http\Livewire\Services\Oexterno\Protests;

use App\Models\Protest;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $protest;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    public function mount(Request $request)
    {
        $this->protest = Protest::where('nota', $request->route('protest'))->with('medProtests')->first();

        if (!$this->protest) {
            abort(404, 'Protesto não encontrado');
        }

    }

    public function removeNoteFromProtest($id)
    {
        if ($id) {
            $this->protest->Notes()->detach($id);
            $this->emit('refreshComponent');
        }
    }

    public function render()
    {
        return view('livewire.services.oexterno.protests.view');
    }
}
