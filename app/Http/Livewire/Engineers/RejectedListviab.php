<?php

namespace App\Http\Livewire\Engineers;

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
    private $filter_group = 'engineer';
    private $filter;

    protected $listeners = [
        'refresh' => '$refresh',
        'refresh_list' => '$refresh',
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

            if (Auth()->user()->Companies->isNotEmpty() && Auth()->user()->engineer) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }


        }

        if (isset($this->filter['responsible']) && $this->filter['responsible']) {
            $query->whereIn('engineer_id', $this->filter['responsible']);
        }

        if (isset($this->filter['company']) && $this->filter['company']) {
            $query->whereIn('company_id', $this->filter['company']);
        }

        if (isset($this->filter['rubrica']) && $this->filter['rubrica']) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city']) && $this->filter['city']) {

            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', trim($this->search))
                    ->orWhereRelation('Note.Orders', 'ordem', trim($this->search));
            });
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

            // dd($this->filter);
        }

        $query = Viability::query()->where('rejected', true)
                ->where('completed', false)
                ->where('status', 4);

        if (!auth()->user()->superadm) {


            if (Auth()->user()->Companies->isNotEmpty() && Auth()->user()->engineer) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }

            // $query->where('engineer_id', Auth()->user()->id);

        }

        if (isset($this->filter['responsible']) && $this->filter['responsible']) {
            $query->whereIn('engineer_id', $this->filter['responsible']);
        }

        if (isset($this->filter['company']) && $this->filter['company']) {
            $query->whereIn('company_id', $this->filter['company']);
        }

        if (isset($this->filter['rubrica']) && $this->filter['rubrica']) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city']) && $this->filter['city']) {

            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', trim($this->search))
                    ->orWhereRelation('Note.Orders', 'ordem', trim($this->search));
            });
        }


        return $query->orderBy('updated_at');
    }


    public function render()
    {
        return view('livewire.engineers.rejected-listviab', [
            'lists' => $this->lists->paginate($this->perPage, ['*'], 'listsPage'),
            'myLists' => $this->my_lists->paginate($this->perPage, ['*'], 'myListsPage'),
        ]);

    }
}
