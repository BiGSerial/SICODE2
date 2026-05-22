<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Partner\DeclaredEquipmentListExport;
use App\Models\Equipment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class WorkEquipment extends Component
{
    use WithPagination;

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

    public function updatedSearch()
    {
        $this->gotoPage(1);
    }

    public function updatedMonth()
    {
        $this->gotoPage(1);

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


    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }
        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filters = $_SESSION['filter'][$this->filter_group];
        }

        $query = Equipment::query();


        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereRelation('WorkReport', function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->whereRelation('WorkReport', 'company_id', Auth()->user()->Company->id);
            }
        }

        if ($this->date_in && $this->date_out) {
            $query->whereBetween('created_at', [$this->date_in, $this->date_out]);
        } elseif ($this->date_in) {
            $query->where('created_at', '>=', $this->date_in);
        } elseif ($this->date_out) {
            $query->where('created_at', '<=', $this->date_out);
        }

        if ($this->search) {
            $query->where(function ($query) {
                $query->Where('patrimony', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('WorkReport.Note', function ($q) {
                        return $q->where('note', 'like', '%' . $this->search . '%')
                            ->orWhere('lexp', 'like', '%' . $this->search . '%');
                    })->orWhereRelation('WorkReport.Orders', function ($q) {
                        return $q->where('ordem', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Filtro de Rubrica
        if (isset($this->filters['rubrica'])) {
            $rubricas = $this->filters['rubrica'];
            $query->whereRelation('WorkReport.Note', function ($q) use ($rubricas) {
                $q->whereIn('rubrica', $rubricas)
                    ->orWhereNull('rubrica');
            });
        }

        // Filtro de Cidade (lexp)
        if (isset($this->filters['city'])) {
            $cities = $this->filters['city'];
            $query->whereRelation('WorkReport.Note', function ($q) use ($cities) {
                $q->whereIn('lexp', $cities)
                    ->orWhereNull('lexp');
            });
        }

        if ($this->equipType) {
            $query->where('type', $this->equipType);
        }

        if ($this->moviment != '') {
            $query->where('installed', $this->moviment);
        }

        if ($this->companySelected) {
            $query->whereRelation('WorkReport', 'company_id', $this->companySelected);
        }



        return $query->orderBy('patrimony');
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
            'month'
        ]);
    }


    public function render()
    {
        return view('livewire.partner.work-equipment', [
            'equipments' => $this->lists->paginate($this->perPage),
        ]);
    }
}
