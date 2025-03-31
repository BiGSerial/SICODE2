<?php

namespace App\Http\Livewire\Partner;

use App\Models\WorkReport;
use Livewire\Component;
use Livewire\WithPagination;

class WorkedRejectedList extends Component
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
        return WorkReport::when(!Auth()->User()->superadm, function ($q) {
            $q->where(function ($query) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
            });
        })
        ->where('rejected', true)
        ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.partner.worked-rejected-list', [
            'lists' => $this->lists
        ]);
    }
}
