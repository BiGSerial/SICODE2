<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\ProtestJob;
use Livewire\Component;
use Livewire\WithPagination;

class Monitoring extends Component
{
    use WithPagination;

    public $perPage = 50;


    public function baseQuery()
    {
        $query = ProtestJob::open()
        ->orderBy('priority', 'desc')
        ->with(['MedProtest', 'Protest', 'Owner:id,name', 'Creator:id,name', 'Closer:id,name']);
        return $query;
    }

    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }


    public function render()
    {
        return view('livewire.protests.dispatch.monitoring', [
            'lists' => $this->lists,
        ]);
    }
}
