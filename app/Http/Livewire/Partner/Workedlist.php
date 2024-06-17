<?php

namespace App\Http\Livewire\Partner;

use App\Models\WorkReport;
use Livewire\Component;

class Workedlist extends Component
{
    public $perPage = 50;

    public function getListsProperty()
    {
        return WorkReport::where('company_id', Auth()->User()->Employee->Contract->company->id)
            ->orderBy('created_at', 'DESC');
    }

    public function render()
    {
        return view('livewire.partner.workedlist', [
            'lists' => $this->lists->paginate($this->perPage)
        ]);
    }
}
