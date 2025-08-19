<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Exports\Protests\ProtestsExportList;
use App\Helpers\TextFormatter;
use App\Jobs\Protests\ProtestExportListJob;
use App\Models\Protest;
use App\Traits\AppliesQueryFilters;
use App\Traits\WildcardFormmater;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\Exportable;

class Lists extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;
    use WildcardFormmater;
    use AppliesQueryFilters;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 100;
    public $search;
    public $advanceSearch;
    public $multisearch = [];
    public $type = "";

    public $showDetails = false;
    public $selected = null;


    // Filters
    public $filtersState = [];


    protected $queryString = [
        'type' => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'filters.updated' => 'onFiltersUpdated',
        'filters.applied' => 'onFiltersUpdated',
    ];

    public function onFiltersUpdated($payload = [])
    {

        $this->filtersState = $payload ?: [];
        $this->resetPage();
    }


    protected function filtersMap(): array
    {
        return [
            'city' => [
                'type' => 'in',
                'column' => 'cidade',
            ],
            'type' => [
                'type' => 'equals',
                'column' => 'tipoNota',
            ],
            'desired_between' => [
                'type' => 'between_dates',
                'column' => 'dtConclusaoDesej',
            ],

        ];
    }

    public function mount()
    {

    }

    public function showDetails($id)
    {
        $this->selected = Protest::find($id);
        $this->showDetails = true;
    }

    public function closeDetails()
    {
        $this->showDetails = false;
        $this->selected = null;
    }



    public function goTo($protestNote)
    {
        return redirect()->route('protests.dispatch.view', [
            'protest' => $protestNote
        ]);
    }


    public function exportToExcel()
    {

        $params = [
         'filtersState' => $this->filtersState,
         'search' => $this->search,
         'multisearch' => $this->multisearch,
    ];

        ProtestExportListJob::dispatch($params, auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'EXPORTAÇÃO INICIADA',
            'text' => 'A exportação foi iniciada, você receberá uma notificação quando estiver pronta.',
            'timer' => 5000,
        ]);
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multisearch = $this->formatTextToArray($this->advanceSearch);
            $this->search = '';
            $this->advanceSearch = '';
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function getListsProperty()
    {
        $query = Protest::query()
            ->whereHas('medProtests', function ($query) {
                $query->where('statusSist', 'MEDA')
                        ->orWhere(function ($query) {
                            $query->where('needsConfirmation', true)
                                ->where('completed', false);
                        });
            });

        // Filtros Dinamicos do componente Livewire v2
        $this->applyFilters($query, $this->filtersState, $this->filtersMap());

        $query->when($this->search, function ($query) {

            $this->multisearch = '';
            $this->advanceSearch = '';
            $this->resetPage();

            $formatted = $this->formatWithWildcard($this->search);

            $query->where(function ($q) use ($formatted) {
                $q->where('nota', $formatted->type, $formatted->search);
            });
        })

         ->when($this->multisearch, function ($query) {
             $query->whereIn('nota', $this->multisearch)
                 ->orWhereRelation('Notes', function ($q) {
                     $q->whereIn('note', $this->multisearch);
                 });
         });

        if (isset($this->filter['city'])) {

            $query->whereIn('cidade', $this->filter['city']);
        }


        $query->with(['medProtests'])

        ->orderBy('dtConclusaoDesej', 'ASC')
        ->orderBy('dtAberturaNota', 'DESC');

        return $query;
    }

    public function render()
    {
        return view('livewire.protests.dispatch.lists', [
            'lists' => $this->lists->paginate($this->perPage)
        ]);
    }
}
