<?php

namespace App\Http\Livewire\Components\Filter;

use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class Filter extends Component
{
    public $model;
    public $column;
    public $values;
    public $direction;
    public $group_filter;
    public $filter;
    public $items = [];
    public $search;
    public $receiverKey;
    public $sendFilter;
    public $receivedValue;



    public function mount($myKey, $sendFilter, $model, $column, $filter, $group_filter, $values, $direction)
    {
        $this->model = app($model);
        $this->column = $column;
        $this->filter = $filter;
        $this->group_filter = $group_filter;
        $this->values = $values;
        $this->direction = $direction;
        $this->receiverKey = $myKey;
        $this->sendFilter = $sendFilter;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->group_filter][$this->filter])) {
            $this->items = $_SESSION['filter'][$this->group_filter][$this->filter];
        } else {
            $this->items = [];
        }
    }

    public function applyFilter()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        $_SESSION['filter'][$this->group_filter][$this->filter] = $this->items;

        $this->emitUp('$refresh');
    }

    public function removeFilter()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->group_filter])) {
            unset($_SESSION['filter'][$this->group_filter]);
            $this->items = [];
        }
    }

    public function senderFilter()
    {

    }

    public function getListFilterProperty()
    {
        $query = $this->model::Query();

        if ($this->search) {
            $query->where($this->column, 'like', "%".$this->search."%");
        }

        return $query->orderBy($this->values, $this->direction)->get();
    }

    public function render()
    {
        return view('livewire.components.filter.filter', [
            'filterLists' => $this->listFilter
        ]);
    }
}
