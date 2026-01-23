<?php

namespace App\Http\Livewire\Engineers;

use App\Helpers\TextFormatter;
use App\Jobs\Engineers\ExportWaitingFiveNotesJob;
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
    public $advanceSearchD5;
    public $advanceSearchNote;
    public $multiD5 = [];
    public $multiNote = [];
    public $type = "";
    public $statusFilter = '';

    public $showDetails = false;


    public $selectall = false;
    public $selected = [];


    // Filters
    public $filtersState = [];



    protected $queryString = [
        'type' => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'statusFilter' => ['except' => '', 'as' => 'status'],
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
            $this->multiD5 = [];
            $this->multiNote = [];
            $this->advanceSearchD5 = "";
            $this->advanceSearchNote = "";
        }
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }


    public function buscarMulti()
    {
        $this->search = "";
        $this->resetPage();
        $this->multiD5 = $this->formatTextToArray($this->advanceSearchD5);
        $this->multiNote = $this->formatTextToArray($this->advanceSearchNote);
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

    private function applyStatusFilter(Builder $base): void
    {
        switch ($this->statusFilter) {
            case 'aguardando_fornecedor':
                $base->where('is_completed', false)
                    ->where('is_archived', false);
                break;
            case 'aguardando_fiscalizacao':
                $base->where('is_completed', true)
                    ->where('is_supervisioned', false)
                    ->where('is_archived', false);
                break;
            case 'aguardando_pagamento':
                $base->where('is_supervisioned', true)
                    ->where('is_archived', false);
                break;
            case 'finalizado':
                $base->where('is_archived', true);
                break;
        }
    }

    /**
     * QUERY BASE (reutilizável)
     */
    private function baseQuery(): Builder
    {
        $base = FiveNote::query()
            ->where('visible_partner', true);

        $this->applyStatusFilter($base);

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

        if (!auth()->user()->superadm) {
            $base->where(function ($q) {
                $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                ->orWhere('company_id', Auth()->user()->Company->id);
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

        if ($this->returnFilterArray('rubrica')) {
            $base->whereRelation('note', function ($q) {
                $q->whereIn('rubrica', $this->returnFilterArray('rubrica'));
            });
        }

        if ($this->returnFilterArray('desired_between')) {
            $dateRange = $this->returnFilterArray('desired_between');
            if (isset($dateRange['start']) && isset($dateRange['end'])) {
                $base->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']]);
            }
        }


        $hasNote = count($this->multiNote) > 0;
        $hasD5 = count($this->multiD5) > 0;

        if ($hasNote || $hasD5) {
            $base->where(function ($query) use ($hasNote, $hasD5) {
                if ($hasNote) {
                    $query->whereHas('note', function ($q) {
                        $q->whereIn('note', $this->multiNote);
                    });
                }

                if ($hasD5) {
                    if ($hasNote) {
                        $query->orWhereIn('note_d5', $this->multiD5);
                    } else {
                        $query->whereIn('note_d5', $this->multiD5);
                    }
                }
            });
        }

        return $base->orderBy('dispatch_at', 'asc');
    }

    public function getListsProperty()
    {
        $page = $this->baseQuery()->paginate($this->perPage);

        $page->load(['note', 'productions', 'company', 'evidenceFiles']);

        return $page;
    }

    public function exportToExcel(): void
    {
        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        ExportWaitingFiveNotesJob::dispatch($this->exportPayload(), (string) $userId);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTACAO INICIADA',
            'text'     => 'Voce recebera uma notificacao quando o arquivo estiver pronto.',
            'timer'    => 5000,
        ]);
    }

    protected function exportPayload(): array
    {
        return [
            'search'        => $this->search,
            'multiD5'       => $this->multiD5,
            'multiNote'     => $this->multiNote,
            'statusFilter'  => $this->statusFilter,
            'filtersState'  => $this->filtersState,
        ];
    }


    public function render()
    {
        return view('livewire.engineers.waiting-five-notes', [
            'lists' => $this->lists,
        ]);
    }


}
