<?php

namespace App\Http\Livewire\Engineers;

use App\Models\Partial;
use Livewire\Component;
use Livewire\WithPagination;

class PartialList extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search;
    public $perPage = 50;
    public $selectedRow;

    public $dt_in;
    public $dt_out;
    public $month;

    // Filters
    private $filter_group = 'partial';
    private $filters;

    protected $queryString = [
        'search' => ['except' => ''],
        'dt_in' => ['except' => '', 'as' => 'in'],
        'dt_out' => ['except' => '', 'as' => 'out'],
    ];

    protected $listeners = [
        'refresh' => '$refresh',
        'refresh_list' => '$refresh'
    ];


    public function pesquisar()
    {
        $this->resetPage();
    }


    public function getListsProperty()
    {
        $this->filters = session('filter.' . $this->filter_group, []);

        $query = Partial::query();
        $query->where('allow', 0)
                   ->Where('deny', 0);

        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty() && Auth()->user()->engineer) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }

        if ($this->search) {
            $query->whereRelation('Note', 'note', trim($this->search))
                    ->orWhereRelation('Note.Orders', 'ordem', trim($this->search));
        }

        if (isset($this->filters['rubrica']) && $this->filters['rubrica'] != '') {
            $query->whereRelation('Note', function ($q) {
                $q->where('rubrica', $this->filters['rubrica']);
            });

        }


        if ($this->dt_in && !$this->dt_out) {
            $query->whereDate('created_at', '>=', $this->dt_in);
        } elseif ($this->dt_out && !$this->dt_in) {
            $query->whereDate('created_at', '<=', $this->dt_out);
        } elseif ($this->dt_in && $this->dt_out) {
            $query->whereBetween('created_at', [$this->dt_in, $this->dt_out]);
        }

        return $query->orderBy('created_at', 'ASC')->paginate($this->perPage);
    }

    public function partialStatus(Partial $partial): array
    {
        $status = [
            'status' => '',
            'color' => '',
        ];

        if ($partial) {
            if ($partial->deny) {
                $status = [
                    'status' => 'REJEITADO',
                    'color' => 'text-bg-danger',
                ];
            } elseif ($partial->payment && $partial->allow) {
                $status = [
                    'status' => 'PAGO',
                    'color' => 'text-bg-success',
                ];
            } elseif ($partial->supervision && !$partial->payment) {
                $status = [
                    'status' => 'EM PAGAMENTO',
                    'color' => 'text-bg-info',
                ];
            } elseif ($partial->allow && !$partial->supervision) {
                $status = [
                    'status' => 'EM FISCALIZAÇÃO',
                    'color' => 'text-bg-info',
                ];
            } else {
                $status = [
                    'status' => 'AVALIAÇÃO',
                    'color' => 'text-bg-warning',
                ];
            }
        }

        return $status;
    }


    public function render()
    {
        return view('livewire.engineers.partial-list', [
            'lists' => $this->lists
        ]);
    }
}
