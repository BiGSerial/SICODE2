<?php

namespace App\Http\Livewire\Reports;

use App\Models\Note;
use Livewire\Component;

class Search extends Component
{
    public $lists;
    public $search;

    public function Search()
    {

        $this->lists = Note::where('note', trim($this->search))->with(['Productions' => function ($query) {
            $query->where('rejected', false);
        }])->first();

    }

    public function render()
    {
        return view('livewire.reports.search');
    }
}
