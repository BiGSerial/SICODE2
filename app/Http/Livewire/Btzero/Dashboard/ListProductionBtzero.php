<?php

namespace App\Http\Livewire\Btzero\Dashboard;

use App\Models\Production;
use Livewire\Component;

class ListProductionBtzero extends Component
{
    public function getListsProperty()
    {
        return Production::select('user_id', 'company_id', 'note_id', 'service_id', 'id', 'updated_at', 'status')->whereRelation('Note', 'rubrica', 'BT Zero')->with('Note', 'User', 'Company')->orderBy('updated_at', 'DESC')->limit(10)->get();
    }


    public function render()
    {
        return view('livewire.btzero.dashboard.list-production-btzero', [
            'productions' => $this->getListsProperty()
        ]);
    }
}
