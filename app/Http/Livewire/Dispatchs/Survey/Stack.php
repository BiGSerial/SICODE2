<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Helpers\TextFormatter;
use App\Models\Production;
use Livewire\{Component, WithPagination};

// TODO: Finalizar a os filtros e demais serviços no Stack de Levantamento. Processo optimizado.
class Stack extends Component
{
    use WithPagination;

    use TextFormatter;
    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 50;
    public $selected = [];
    public $statusFilter = null;
    public $search = '';
    public $advancedSearch;
    public $multiSearch = [];

    protected $queryString = [
        'statusFilter' => ['except' => null],
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    protected $listeners = [
        'resetFilters',
        'refresh_list' => '$refresh',
    ];

    public function mount($service)
    {

        $this->service = $service;
    }

    public function resetFilters()
    {
        $this->reset('statusFilter', 'search', 'page');
        $this->multiSearch = [];
        $this->advancedSearch = null;
        $this->search = null;
    }

    public function updatedSearch()
    {
        if (!trim($this->search)) {
            return;
        }

        $this->reset('statusFilter', 'page', 'multiSearch');
        $this->advancedSearch = null;

    }


    public function buscarMulti()
    {
        if (!trim($this->advancedSearch)) {
            return;
        }

        $this->reset('statusFilter', 'page');
        $this->multiSearch = $this->formatTextToArray($this->advancedSearch);

        if (count($this->multiSearch) > 0) {
            $this->search = null;
            $this->advancedSearch = null;

            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function baseQuery()
    {
        return Production::Query()
            ->where('service_id', $this->service)
            ->where('completed', false)
            // ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->with(['wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'service:id,uuid,service',
                'user:id,name',
                'note:id,note,nstats,dt_status,rubrica,postes,lexp',

                'note.orders:id,note_id,moaberto'
                ])
        ;
    }

    public function getListsProperty()
    {
        $query = $this->baseQuery()
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->whereHas('note', function ($query) {
                        $query->where('note', 'like', '%' . $this->search . '%')
                              ->orWhere('rubrica', 'like', '%' . $this->search . '%')
                              ->orWhere('lexp', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('productions.odi', 'like', '%' . $this->search . '%')
                    ->orWhere('productions.odd', 'like', '%' . $this->search . '%')
                    ->orWhere('productions.ods', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('note.orders', function ($q) {
                        $q->where('ordem', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->when(count($this->multiSearch) > 0, function ($q) {
                $q->where(function ($q) {
                    $q->whereHas('note', function ($query) {
                        $query->whereIn('note', $this->multiSearch)
                              ->orWhere('rubrica', $this->multiSearch)
                              ->orWhere('lexp', $this->multiSearch);
                    })
                    ->orWhereHas('user', function ($q) {
                        $q->whereIn('name', $this->multiSearch);
                    })
                    ->orWhereHas('note.orders', function ($q) {
                        $q->whereIn('ordem', $this->multiSearch);
                    });
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('productions.status', $this->statusFilter);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')

            ->orderBy('att_at', 'asc')
            ->orderBy('productions.id', 'asc')
            ->paginate($this->perPage);

        return $query;
    }

    public function render()
    {
        $statusList = $this->baseQuery()
            ->select('productions.status as status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            })
            ->toArray();

        return view('livewire.dispatchs.survey.stack', [
            'lists' => $this->lists,
            'statusList' => $statusList,
        ]);
    }
}
