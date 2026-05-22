<?php

namespace App\Http\Livewire\Reports;

use App\Exports\Notesreport;
use App\Models\Note;
use Livewire\{Component, WithPagination};

class Advancedsearch extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search;

    public $perPage = 50;

    public $note_type = '';

    public $group2 = [];

    public $status = [];

    public $centerJob_s = [];

    public $centerJob = '';

    public $search_f;

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'find'],
        'note_type' => ['except' => '', 'as' => 'tipo'],
    ];

    public function Search()
    {

    }

    public function mount()
    {
        if (session_status() == PHP_SESSION_NONE) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filtro']['searchAdvanced'])) {
            $this->group2      = $_SESSION['filtro']['searchAdvanced']['group2'];
            $this->status      = $_SESSION['filtro']['searchAdvanced']['status'];
            $this->centerJob_s = $_SESSION['filtro']['searchAdvanced']['centerJob'];
        }

        // $this->centerJob = Note::select('centerjob')->distinct()->orderBy('centerjob')->get();
    }

    public function applyFilter()
    {

        if (session_status() == PHP_SESSION_NONE) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $_SESSION['filtro']['searchAdvanced']['group2']    = $this->group2;
        $_SESSION['filtro']['searchAdvanced']['status']    = $this->status;
        $_SESSION['filtro']['searchAdvanced']['centerJob'] = $this->centerJob_s;

        $this->search_f = '';

    }

    public function removeFilter()
    {
        if (session_status() == PHP_SESSION_NONE) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $this->group2      = [];
        $this->status      = [];
        $this->centerJob_s = [];
        $this->search_f    = '';

        unset($_SESSION['filtro']['searchAdvanced']);
    }

    public function exportToExcel()
    {
        // return (new ExportDDExcel())->exportDD($this->selected, $this->service->service)->download(date('YmdHis-').'exportDD.xlsx');
        return (new Notesreport($this->lists->get()))->download(date('YmdHis-') . '-NoteReport-Sicode.xlsx');
    }

    public function getFiltrosProperty()
    {
        $query = Note::query();

        $query->Where('nstats', '<', 98);

        return $query;

    }

    public function getListsProperty()
    {
        $this->centerJob = Note::where('centerjob', 'like', "%{$this->search_f}%")
            ->select('centerjob')
            ->distinct()
            ->orderBy('centerjob')
            ->get();

        return Note::Where(function ($q) {
            $q->where('note', 'like', '%' . $this->search . '%')
                ->orWhere('group1', 'like', '%' . $this->search . '%')
                ->orWhere('group2', 'like', '%' . $this->search . '%')
                ->orWhere('group3', 'like', '%' . $this->search . '%')
                ->orWhere('group4', 'like', '%' . $this->search . '%')
                ->orWhere('group5', 'like', '%' . $this->search . '%')
                ->orWhere('lexp', 'like', '%' . $this->search . '%')
                ->orWhere('rubrica', 'like', '%' . $this->search . '%')
                ->orWhere('numPedido', 'like', '%' . $this->search . '%')
                ->orWhere('material', 'like', '%' . $this->search . '%');
        })->When($this->note_type, function ($q) {
            return $q->where('type_note', $this->note_type);
        })
            ->when($this->status, function ($q) {
                return $q->whereIn('nstats', $this->status);
            })
            ->when($this->group2, function ($q) {
                return $q->whereIn('group2', $this->group2);
            })
            ->when($this->centerJob_s, function ($q) {
                return $q->whereIn('centerjob', $this->centerJob_s)
                    ->orWhere(function ($q) {
                        $q->where('type_note', 1)
                            ->where('centerjob', '');
                    });
            })
            ->Where('nstats', '<', 98)
            ->OrderBy('type_note', 'DESC')
            ->OrderBy('days_left');

    }

    public function render()
    {
        return view('livewire.reports.advancedsearch', [
            'lists'   => $this->lists->paginate($this->perPage),
            'filtros' => $this->filtros,

        ]);
    }
}
