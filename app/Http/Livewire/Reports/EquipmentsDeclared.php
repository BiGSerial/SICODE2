<?php

namespace App\Http\Livewire\Reports;

use App\Exports\Partner\DeclaredEquipmentListExport;
use App\Helpers\TextFormatter;
use App\Models\Company;
use App\Models\Equipment;
use App\Traits\WildcardFormmater;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class EquipmentsDeclared extends Component
{
    use WithPagination;
    use TextFormatter;
    use WildcardFormmater;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search;
    public $advancedSearch;
    public $multipleSearch;
    public $equipType;
    public $moviment;
    public $companySelected;
    public $date_in;
    public $date_out;
    public $month;
    public $company_list;


    private $filter_group = 'equipment';
    private $filters;



    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'equipType' => ['except' => '', 'as' => 'tipo'],
        'moviment' => ['except' => '', 'as' => 'movimento'],
        'companySelected' => ['except' => '', 'as' => 'empresa'],
        'date_in' => ['except' => '', 'as' => 'data_inicial'],
        'date_out' => ['except' => '', 'as' => 'data_final'],
        'month' => ['except' => '', 'as' => 'mes'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',

    ];

    public function mount()
    {
        $this->company_list = Company::has('WorkReports')->orderBy('name')->get();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->advancedSearch = null;
        $this->multipleSearch = null;
    }

    public function updatedMonth()
    {
        $this->resetPage();

        if ($this->month) {
            $this->date_in = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->date_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        } else {
            $this->date_in = null;
            $this->date_out = null;
        }
    }

    public function export_excel()
    {
        return (new DeclaredEquipmentListExport($this->lists))->download(now()->format('YmdHis').'-Equipamentos.xlsx');
    }

    public function multiSearch()
    {
        if ($this->advancedSearch) {
            $this->multipleSearch = $this->formatTextToArray($this->advancedSearch);
            $this->advancedSearch = null;
            $this->resetPage();




            $this->emitSelf('refresh_list');
        }

        $this->dispatchBrowserEvent('hideModal');
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }
        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filters = $_SESSION['filter'][$this->filter_group];
        }

        $query = Equipment::query();

        if ($this->date_in && $this->date_out) {
            $query->whereBetween('created_at', [$this->date_in, $this->date_out]);
        } elseif ($this->date_in) {
            $query->where('created_at', '>=', $this->date_in);
        } elseif ($this->date_out) {
            $query->where('created_at', '<=', $this->date_out);
        }

        if ($this->search) {
            $query->where(function ($query) {
                $wildcard = $this->formatWithWildcard($this->search);

                $query->Where('patrimony', $wildcard->type, $wildcard->search)
                    ->orWhereHas('WorkReport.Note', function ($q) use ($wildcard) {
                        $q->where('note', $wildcard->type, $wildcard->search)
                            ->orWhere('lexp', $wildcard->type, $wildcard->search);
                    })->orWhereHas('WorkReport.Orders', function ($q) use ($wildcard) {
                        $q->where('ordem', $wildcard->type, $wildcard->search);
                    });
            });
        }

        if ($this->multipleSearch) {

            $query->where(function ($query) {
                $query->whereIn('patrimony', $this->multipleSearch)
                    ->orWhereHas('WorkReport.Note', function ($q) {
                        $q->whereIn('note', $this->multipleSearch)
                            ->orWhereIn('lexp', $this->multipleSearch);
                    })->orWhereHas('WorkReport.Orders', function ($q) {
                        $q->whereIn('ordem', $this->multipleSearch);
                    });
            });
        }


        // Filtro de Rubrica
        if (isset($this->filters['rubrica'])) {
            $rubricas = $this->filters['rubrica'];
            $query->whereHas('WorkReport.Note', function ($q) use ($rubricas) {
                $q->whereIn('rubrica', $rubricas);
            });
        }

        // Filtro de Cidade (lexp)
        if (isset($this->filters['city'])) {
            $cities = $this->filters['city'];
            $query->whereHas('WorkReport.Note', function ($q) use ($cities) {
                $q->whereIn('lexp', $cities);
            });
        }

        if ($this->equipType) {
            $query->where('type', $this->equipType);
        }

        if ($this->moviment !== '' && $this->moviment !== null) {
            $query->where('installed', $this->moviment);
        }

        if ($this->companySelected) {
            $query->whereHas('WorkReport', function ($q) {
                $q->where('company_id', $this->companySelected);
            });
        }

        return $query->orderBy('created_at');
    }

    public function cleanAll()
    {
        $this->reset([
            'search',
            'advancedSearch',
            'multipleSearch',
            'equipType',
            'moviment',
            'companySelected',
            'date_in',
            'date_out',
            'month',
            'companySelected'
        ]);
    }


    public function render()
    {
        return view('livewire.reports.equipments-declared', [
            'equipments' => $this->lists->paginate($this->perPage),
        ]);
    }
}
