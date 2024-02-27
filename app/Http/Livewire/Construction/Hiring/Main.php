<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Order;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';



    public $service;
    public $advanceSearch;
    public $search;
    public $selectAll;
    public $selected = [];
    public $typeNote;

    public $perPage = 50;

    // Filters
    private $filter_group = "hiring";
    private $filter;

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();


    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Adicionar os IDs ausentes de $selected
            foreach ($this->lists->pluck('id')->toArray() as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            // Criar um novo array $selected com os IDs que devem ser mantidos
            $newSelected = [];
            foreach ($this->selected as $id) {
                if (!in_array($id, $this->lists->pluck('id')->toArray())) {
                    $newSelected[] = $id;
                }
            }
            $this->selected = $newSelected;
        }
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }


        return Order::LeftJoin('notes', 'orders.note_id', '=', 'notes.id')
    ->whereHas('note', function ($query) {
        $query->where(function ($query) {
            $query->when($this->typeNote, function ($q) {
                $q->where('type_note', $this->typeNote);
            })
            ->whereIn('nstats', [47, 48, 49])
            ->orWhere('centerjob', 'like', 'CONSTR%');
        });
    })
    ->select('orders.*', 'notes.id', 'notes.days_left', 'notes.type_note', 'notes.note')
    ->orderBy('notes.type_note', "DESC")
    ->orderBy('notes.days_left')
    ->orderBy('notes.note')
    ->paginate($this->perPage);

    }

    public function render()
    {
        if (empty(array_diff($this->lists->pluck('id')->toArray(), $this->selected))) {
            $this->selectAll = true;
        } else {
            $this->selectAll = false;
        }

        return view('livewire.construction.hiring.main', [
            'lists' => $this->lists
        ]);
    }
}
