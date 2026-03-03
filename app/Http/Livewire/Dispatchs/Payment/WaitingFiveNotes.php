<?php

namespace App\Http\Livewire\Dispatchs\Payment;

use App\Helpers\TextFormatter;
use App\Models\FiveNote;
use App\Traits\AppliesQueryFilters;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\Exportable;

class WaitingFiveNotes extends Component
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


    public $selectall = false;
    public $selected = [];
    public $passiveFilter = 'current';


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
         // MD5 of SICODE
    ];

    public function updatedSearch()
    {
        $this->resetPage();
        if (!$this->search) {
            $this->multisearch = [];
            $this->advanceSearch = "";
        }
    }


    public function buscarMulti()
    {
        $this->search = "";
        $this->resetPage();
        $this->multisearch = $this->formatTextToArray($this->advanceSearch);
    }

    public function onFiltersUpdated($payload = [])
    {

        $this->filtersState = $payload ?: [];
        $this->resetPage();



    }




    public function setSelectAll()
    {
        if (!$this->lists) {
            return;
        }

        $visibleItems = $this->lists->items();

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);



        if ($this->selectall) {

            foreach ($visibleItems as $note) {

                $id = (int) $note->id;

                if (isset($selectedSet[$id])) {
                    continue;
                }

                $selectedSet[$id] = true;
            }
        } else {
            foreach ($visibleItems as $note) {
                unset($selectedSet[(int) $note->id]);
            }
        }

        $this->selected = array_map('intval', array_keys($selectedSet));

    }

    /**
     * Marca/desmarca o checkbox "selecionar todos" de acordo com os itens visíveis
     */
    public function checkAllSelect($items)
    {

        $eligiblePage = [];

        foreach ($items as $note) {
            $eligiblePage[] = (int) $note->id;
        }

        // selectall fica true quando TODOS os elegíveis da página estão selecionados
        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);
        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectall = false;
                return false;
            }
        }

        $this->selectall = true;
        return true;
    }

    protected function recomputeSelectAllFor(array $items): void
    {

        $eligiblePage = [];

        foreach ($items as $note) {
            $eligiblePage[] = (int) $note->id;
        }

        // se não há elegíveis na página, não marcar o master
        if (empty($eligiblePage)) {
            $this->selectall = false;
            return;
        }

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);
        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectall = false;
                return;
            }
        }

        $this->selectall = true;
    }

    private function returnFilterArray($key)
    {
        if (is_array($this->filtersState[$key] ?? null)) {
            return $this->filtersState[$key] ?? [];
        } else {
            return $this->filtersState[$key] ?? null;
            ;
        }
    }

    /**
     * QUERY BASE (reutilizável)
     */
    private function baseQuery(): Builder
    {
        $base = FiveNote::query()
            ->where('is_archived', false);

        $base->when($this->passiveFilter === 'current', fn ($q) => $q->where('isPassive', false))
            ->when($this->passiveFilter === 'passive', fn ($q) => $q->where('isPassive', true));

        if ($this->search) {


            $search = $this->formatWithWildcard($this->search);

            $base->where(function ($query) use ($search) {
                $query->whereHas('note', function ($q) use ($search) {
                    $q->where('note', $search->type, $search->search);
                })
                    ->orWhere('note_d5', $search->type, $search->search)
                    ->orWhere('reason', $search->type, $search->search)
                    ->orWhere('codify', $search->type, $search->search)
                    ->orWhereHas('company', function ($q) use ($search) {
                        $q->where('name', $search->type, $search->search);
                    });
            });
        }

        if ($this->returnFilterArray('company')) {
            $base->whereIn('company_id', $this->returnFilterArray('company'));
        }

        if ($this->returnFilterArray('type')) {
            $base->whereRelation('note', 'type_note', $this->returnFilterArray('type'));

        }

        if ($this->returnFilterArray('city')) {
            $base->whereRelation('note', function ($q) {
                $q->whereIn('nexp', $this->returnFilterArray('city'));
            });
        }

        if ($this->returnFilterArray('desired_between')) {
            $dateRange = $this->returnFilterArray('desired_between');
            if (isset($dateRange['start']) && isset($dateRange['end'])) {
                $base->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']]);
            }
        }


        if (count($this->multisearch) > 0) {

            $base->where(function ($query) {
                $query->whereHas('note', function ($q) {
                    $q->whereIn('note', $this->multisearch);
                })
                    ->orWhereIn('note_d5', $this->multisearch)
                    ->orWhereIn('reason', $this->multisearch)
                    ->orWhereIn('codify', $this->multisearch)
                    ->orWhereHas('company', function ($q) {
                        $q->where('name', $this->multisearch);
                    });
            });
        }

        return $base;
    }

    public function getListsProperty()
    {
        $page = $this->baseQuery()->paginate($this->perPage);

        $page->load(['note', 'productions', 'company', 'evidenceFiles']);

        return $page;
    }

    public function updatedPassiveFilter(): void
    {
        $this->resetPage();
    }


    public function render()
    {
        return view('livewire.dispatchs.payment.waiting-five-notes', [
            'lists' => $this->lists,
        ]);
    }


}
