<?php

namespace App\Http\Livewire\Btzero;

use App\Models\RamalReport;
use Livewire\Component;
use Livewire\WithPagination;

class RamalRejecteds extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage;
    public $search;


    protected $listeners = [
        'refresh_rejected' => '$refresh',
    ];


    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];



    public function getListsProperty()
    {
        return RamalReport::where('rejected', true)
        ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.btzero.ramal-rejecteds', [
            'lists' => $this->lists
        ]);
    }
}
