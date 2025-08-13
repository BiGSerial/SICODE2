<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Exports\Protests\ProtestsExportList;
use App\Helpers\TextFormatter;
use App\Jobs\Protests\ProtestExportListJob;
use App\Models\Protest;
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
    private $filter_group = 'oexterno';
    private $filter;


    protected $queryString = [
        'type' => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

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

        if (!session()->isStarted()) {
            session()->start();
        }
        $this->filter = session("filter.{$this->filter_group}", []);



        $param = [
            'search' => $this->search,
            'type' => $this->type,
            'multisearch' => $this->multisearch,
            'filter' => $this->filter,
        ];

        ProtestExportListJob::dispatch($param, auth()->id());
        // $export = new ProtestsExportList($this->getListsProperty());
        // return $export->download('protests_list_' . date('Y-m-d_H-i-s') . '.xlsx');

        $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'EXPORTAÇÃO INICIADA',
                    'text'     => 'A exportação foi iniciada, você receberá uma notificação quando estiver pronta.',
                    'timer'    => 5000,
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


        if (!session()->isStarted()) {
            session()->start();
        }
        $this->filter = session("filter.{$this->filter_group}", []);

        $query = Protest::query()
            ->whereHas('medProtests', function ($query) {
                $query->where('statusSist', 'MEDA')
                        ->orWhere(function ($query) {
                            $query->where('needsConfirmation', true)
                                ->where('completed', false);
                        });
            })

            ->when($this->search, function ($query) {

                $this->multisearch = '';
                $this->advanceSearch = '';
                $this->resetPage();

                $formatted = $this->formatWithWildcard($this->search);

                $query->where(function ($q) use ($formatted) {
                    $q->where('nota', $formatted->type, $formatted->search);
                });
            })
            ->when($this->type, function ($query) {
                $query->where('tipoNota', $this->type);
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
