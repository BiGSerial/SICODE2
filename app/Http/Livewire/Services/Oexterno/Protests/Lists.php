<?php

namespace App\Http\Livewire\Services\Oexterno\Protests;

use App\Helpers\TextFormatter;
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
    public $perPage = 200;
    public $search;
    public $advanceSearch;
    public $multisearch = [];
    public $typeNote = "";


    // Filters
    private $filter_group = 'oexterno';
    private $filter;


    protected $queryString = [
       'typeNote' => ['except' => '', 'as' => 'tipo'],
       'search'  => ['except' => '', 'as' => 'buscar'],
       'page'    => ['except' => 1, 'as' => 'p'],
       'perPage' => ['as' => 'pp'],
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
            ->whereHas('medProtests', function ($query) {
                $query->where('statusSist', 'MEDA');
            })

            ->when($this->search, function ($query) {

                $formatted = $this->formatWithWildcard($this->search);



                $query->where(function ($q) use ($formatted) {
                    $q->where('nota', $formatted->type, $formatted->search);
                });
            })
            // ->when($this->typeNote, function ($query) {
            //     $query->where('type_note', $this->typeNote);
            // })
            ->with('medProtests')
            ->orderBy('dtConclusaoDesej', 'ASC')
            ->orderBy('dtAberturaNota', 'DESC')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.services.oexterno.protests.lists', [
            'lists' => $this->lists
        ]);
    }
}
