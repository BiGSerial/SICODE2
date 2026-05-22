<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Helpers\TextFormatter;
use App\Models\Note;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class Lookatnotes extends Component
{
    use TextFormatter;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $typeNote = '';
    public $search;
    public $advancedSearch;
    public $multipleSearch = [];
    public $perPage = 50;

    // Filters
    public $filter_group = 'lookatnotes';
    public $filter;

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function buscarMulti()
    {
        if ($this->advancedSearch) {

            $this->multipleSearch = $this->formatTextToArray($this->advancedSearch);

            if (count($this->multipleSearch) > 0) {
                $this->search = null;
                $this->goToPage(1);
                $this->advancedSearch = null;

                $this->dispatchBrowserEvent('hideModal');
            }
        }
    }

    public function updatedSearch()
    {
        if (trim($this->search)) {
            $this->advancedSearch = null;
            $this->multipleSearch = [];
            $this->goToPage(1);
        }
    }


    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Note::query();

        $query->where(function ($query) {
            $query->where(function ($qq) {
                $qq->whereIn('nstats', [46, 47, 48, 49, 50])
                ->whereNotIn('rubrica', ['Incoporação'])
                ->where('type_note', 2);
            })
            ->orWhere(function ($qq) {
                $qq->where(function ($qs) {
                    $qs->where('type_note', 1)
                    ->where('centerjob', 'like', 'VIAB%');
                })
                ->orWhere(function ($qq) {
                    $qq->orWhereNull('centerjob')
                    ->where('type_note', 1);
                });
            });
        })
        ->whereHas('Orders', function ($q) {
            $q->where('statusSist', 'not like', 'ENTE%')
            ->where('statusSist', 'not like', 'ENCE%')
            ->whereHas('Operations', function ($sq) {
                $sq->where('operacao', '0010')
                   ->where('status', 'like', 'ABER%');
            });
        })

        ->where(function ($q) {
            $q->where('txpriority', '!=', 'Emergente')
              ->orWhereNull('txpriority');
        })
        ->with([
           'orders' => function ($q) {
               $q->where('statusSist', 'not like', 'ENT%')
                   ->where('statusSist', 'not like', 'ENC%')
                   ->orderBy('ordem');
           },
           'orders.operations' => function ($q) {
               $q->where('operacao', '0010');
           },
        ]);



        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        if ($this->search) {
            $search_term = "%{$this->search}%"; //Define a variável fora das closures
            $query->where(function ($q) use ($search_term) {
                $q->where('note', 'like', $search_term)
                  ->orWhereHas('orders', function ($q) use ($search_term) {
                      $q->where('ordem', 'like', $search_term);
                  });
            });
        }

        if ($this->multipleSearch) {
            $multipleSearch = $this->multipleSearch; //Define a variável fora das closures
            $query->where(function ($q) use ($multipleSearch) {
                $q->whereIn('note', $multipleSearch)
                  ->orWhereHas('orders', function ($q) use ($multipleSearch) {
                      $q->whereIn('ordem', $multipleSearch);
                  });
            });
        }


        if (isset($this->filter['city'])) {
            $query->whereIn('lexp', $this->filter['city']);
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if (isset($this->filter['operacao'])) {
            $query->whereHas('orders.operations', function ($q) {
                $q->where('operacao', '0010')
                  ->whereIn('cenTrab', $this->filter['operacao']);
            });
        }

        return $query
            ->orderBy('dt_status', 'ASC')
            ->paginate($this->perPage);
    }


    public function render()
    {
        return view('livewire.construction.hiring.lookatnotes',
            [
                'lists' => $this->lists,
                'service' => $this->service,
            ]
        );
    }
}
