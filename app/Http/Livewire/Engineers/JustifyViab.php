<?php

namespace App\Http\Livewire\Engineers;

use App\Models\City;
use App\Models\Viability;
use Livewire\Component;
use Livewire\WithPagination;

class JustifyViab extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search;
    public $perPage = 50;
    public $cities;



    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'page' => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function getListsProperty()
    {
        $query = Viability::query();

        $query->join('tacit_comments', 'viabilities.id', '=', 'tacit_comments.viability_id')
            ->where('tacit_comments.granted', false)
            ->where('tacit_comments.dismissed', false)
            ->where('viabilities.tacit', true)
            ->orderBy('tacit_comments.justified_at', 'desc');


            if (!auth()->user()->superadm) {


            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());

        }

        return $query->select('viabilities.*', 'tacit_comments.justified_at as comment_justified_at');
    }



    public function render()
    {
        return view('livewire.engineers.justify-viab', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
