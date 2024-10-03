<?php

namespace App\Http\Livewire\Responsible;

use App\Models\Viability;
use Livewire\Component;
use Livewire\WithPagination;

class RejectedListviab extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search;

    // Filters
    private $filter_group = 'partner';
    private $filter;

    protected $listeners = [
        'refresh' => '$refresh',
    ];


    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    // Sempre que atualizar a página, coloca para exibir o primeiro reegistro.
    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::query()->where('rejected', true)
                ->where('completed', false)
                ->where('status', '!=', 4);

        if (!auth()->user()->superadm) {

            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->where('engineer_id', Auth()->user()->id);
        }

        return $query->orderBy('updated_at');
    }


    public function getMyListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::query()->where('rejected', true)
                ->where('completed', false)
                ->where('status', 4);

        if (!auth()->user()->superadm) {


            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->where('engineer_id', Auth()->user()->id);

        }

        return $query->orderBy('updated_at');
    }


    public function render()
    {
        return view('livewire.responsible.rejected-listviab', [
            'lists' => $this->lists->paginate($this->perPage, ['*'], 'listsPage'),
            'myLists' => $this->my_lists->paginate($this->perPage, ['*'], 'myListsPage'),
        ]);

    }
}
