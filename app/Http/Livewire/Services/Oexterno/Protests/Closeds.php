<?php

namespace App\Http\Livewire\Services\Oexterno\Protests;

use App\Helpers\TextFormatter;
use App\Models\Protest;
use App\Traits\WildcardFormmater;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\Exportable;

class Closeds extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;
    use WildcardFormmater;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 200;
    public $search;
    public $advanceSearch;
    public $multisearch = [];
    public $typeNote = "";

    public $inPrazo = 0;


    // Filters
    private $filter_group = 'oexterno';
    private $filter;


    protected $queryString = [
       'typeNote' => ['except' => '', 'as' => 'tipo'],
       'search'  => ['except' => '', 'as' => 'buscar'],
       'page'    => ['except' => 1, 'as' => 'p'],
       'perPage' => ['as' => 'pp'],
       'inPrazo' => ['except' => '', 'as' => 'emPrazo'],
    ];

    public function mount($service)
    {
        $this->service = $service;
    }

    public function goTo($protestNote)
    {
        return redirect()->route('services.protests.view', [
            'service' => $this->service,
            'protest' => $protestNote
        ]);
    }

    public function getListsProperty()
    {
        return Protest::query()
            ->whereDoesntHave('medProtests', function ($query) {
                $query->where('statusSist', 'MEDA');
            })
            ->when($this->inPrazo, function ($query) {
                if ($this->inPrazo == 1) {
                    $query->whereHas('medProtests', function ($q) {
                        $q->whereColumn('med_protests.dtFimMedida', '>', 'protests.dtConclusaoDesej');
                    });
                } elseif ($this->inPrazo == 2) {
                    $query->whereDoesntHave('medProtests', function ($q) {
                        $q->whereColumn('med_protests.dtFimMedida', '>', 'protests.dtConclusaoDesej');
                    })->has('medProtests');
                }
            })
            ->when($this->search, function ($query) {
                $formatted = $this->formatWithWildcard($this->search);
                $query->where('nota', $formatted->type, $formatted->search);
            })
            ->with(['MedProtests'])

            ->orderBy('dtConclusaoDesej', 'ASC')
            ->orderBy('dtAberturaNota', 'DESC')
            ->paginate($this->perPage);

    }

    public function render()
    {
        return view('livewire.services.oexterno.protests.closeds', [
            'lists' => $this->lists
        ]);
    }
}
