<?php

namespace App\Http\Livewire\Btzero\Dashboard;

use App\Models\Production;
use Livewire\Component;

class ListProductionBtzero extends Component
{
    public $note;

    protected $listeners = [
        'selectNote',
        'refreshList' => '$refresh'
    ];


    public function selectNote($id)
    {

        $this->note = $id;

        // dd($id);
        $this->emit('refreshList');
    }

    public function getListsProperty()
    {
        return Production::when($this->note, function ($query) {
            $query->where('note_id', $this->note);
        })
                ->select('user_id', 'company_id', 'note_id', 'service_id', 'id', 'updated_at', 'status')
                ->with('Note', 'User', 'Company')
                ->orderBy('updated_at', 'DESC')
                ->limit(10)
                ->get();
    }


    public function render()
    {
        return view('livewire.btzero.dashboard.list-production-btzero', [
            'productions' => $this->getListsProperty()
        ]);
    }
}
