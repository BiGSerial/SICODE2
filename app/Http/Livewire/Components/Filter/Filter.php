<?php

namespace App\Http\Livewire\Components\Filter;

use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class Filter extends Component
{
    public $modelo;
    public $coluna;
    public $valores;
    public $direction;
    public $group_filter;
    public $filter;
    public $items = [];
    public $search;

    public function mount($model, $column, $filter, $group_filter, $values, $direction = 'ASC')
    {
        $this->modelo = app($model);
        $this->coluna = $column;
        $this->filter = $filter;
        $this->group_filter = $group_filter;
        $this->valores = $values;
        $this->direction = $direction;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filtro'][$this->group_filter][$this->filter])) {
            $this->items = $_SESSION['filtro'][$this->group_filter][$this->filter];
        } else {
            $this->items = [];
        }
    }

    public function applyFilter()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        $_SESSION['filtro'][$this->group_filter][$this->filter] = $this->items;
    }

    public function removeFilter()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filtro'][$this->group_filter])) {
            unset($_SESSION['filtro'][$this->group_filter]);
            $this->items = [];
        }
    }

    public function getListFilterProperty()
    {
        $query = $this->modelo::Query();

        if ($this->search) {
            $query->where($this->coluna, 'like', "%".$this->search."%");
        }

        return $query->orderBy($this->valores, $this->direction)->get();
    }

    public function render()
    {
        return view('livewire.components.filter.filter', [
            'filterLists' => $this->listFilter
        ]);
    }
}
