<?php

namespace App\Http\Livewire\Partner;

use App\Models\Viability;
use Livewire\Component;
use Livewire\WithPagination;

class TacitList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search;

    // Filters
    private $filter_group = 'partner';
    private $filter;


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

        $query = Viability::query()
            ->doesntHave('Justification')
            ->whereBetween('tacit_at', [now()->subDays(7)->startOfDay(), now()->endOfDay()])
            ->where('approved', true)
            ->where('completed', true)
            ->where('tacit', true);

        if (!auth()->user()->superadmin) {

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', $this->search)
                    ->orWhereRelation('Note.Orders', 'ordem', $this->search);
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        return $query->orderBy('tacit_at', 'desc');
    }




    public function render()
    {
        return view('livewire.partner.tacit-list', [
            'lists' => $this->lists->paginate($this->perPage, ['*'], 'listsPage'),

        ]);

    }
}
