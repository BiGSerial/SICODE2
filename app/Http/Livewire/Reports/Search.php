<?php

namespace App\Http\Livewire\Reports;

use App\Models\Note;
use Livewire\Component;

class Search extends Component
{
    public $search;

    protected $queryString = [
        'search' => ['except' => '', 'as' => 's']
        ];

    public function Search()
    {




    }

    public function getBuscarProperty()
    {
        return Note::where('note', trim($this->search))->with(['Productions' => function ($query) {
            $query->where('rejected', false);
        }])->first();
    }

    public function render()
    {
        return view('livewire.reports.search', [
            'lists' => $this->buscar
        ]);
    }
}
