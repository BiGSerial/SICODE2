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
    public $receivedValue = [];
    public $isRefreshing = false;



    protected $listeners = [
        'refresh_filter' => 'refreshme',
        'refresh_myself' => '$refresh',

    ];

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

        if (isset($_SESSION['filter'][$this->group_filter][$this->column])) {
            $this->items = $_SESSION['filter'][$this->group_filter][$this->column];
        } else {
            $this->items = [];
        }

        if ($this->sendFilter && (!isset($_SESSION['filter'][$this->group_filter]['receiver'][$this->sendFilter]) || !in_array($this->column, $_SESSION['filter'][$this->group_filter]['receiver'][$sendFilter]))) {
            $_SESSION['filter'][$this->group_filter]['receiver'][$sendFilter][] = $this->column;
        }


    }

    public function refreshme($myKey, $values = [])
    {


        if ($this->receiverKey === $myKey) {

            $this->isRefreshing = true;

            if (!empty($values)) {


                $columnExists = false;
                $newValue = $values;

                // Verificar se já existe um registro com a mesma chave "column"
                foreach ($this->receivedValue as $key => $received) {
                    if ($received['column'] === $newValue['column']) {
                        // Substituir os valores do registro existente
                        $this->receivedValue[$key] = $newValue;
                        $columnExists = true;
                        break;
                    }
                }

                // Se não houver registro com a mesma chave "column", adicione um novo registro
                if (!$columnExists) {
                    $this->receivedValue[] = $newValue;
                }

                $this->items = $this->listFilter->unique($this->column)->pluck($this->column)->toArray();
                $this->applyFilter();

            } else {
                $this->receivedValue = [];
                $this->items = [];
            }

            $this->emitSelf('refresh_myself');

            $this->isRefreshing = false;
        }


    }

    public function addictValue($value)
    {
        // dd($value);
        $this->items[] = $value;
        $this->emit('refresh_filter', $this->sendFilter);
    }

    public function applyFilter()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        $_SESSION['filter'][$this->group_filter][$this->column] = $this->items;

        $this->emitUp('refresh_list');
        $this->emit('refresh_filter', $this->sendFilter, ['column' => $this->column, 'values' => $this->items]);
    }



    public function removeFilter()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->group_filter])) {
            unset($_SESSION['filter'][$this->group_filter]);
            // unset($_SESSION['filter'][$this->group_filter]['receiver']);
            $this->items = [];
        }

        $this->emitUp('refresh_list');
        $this->emit('refresh_filter', $this->sendFilter);
    }


    public function getListFilterProperty()
    {
        if (isset($_SESSION['filter'][$this->group_filter]['receiver'][$this->receiverKey])) {

            foreach ($_SESSION['filter'][$this->group_filter]['receiver'][$this->receiverKey] as $filter) {
                if (isset($_SESSION['filter'][$this->group_filter][$filter])) {
                    $columnExists = false;
                    $newValue = [
                        'column' => $filter,
                        'values' => $_SESSION['filter'][$this->group_filter][$filter]
                    ];

                    // Verificar se já existe um registro com a mesma chave "column"
                    foreach ($this->receivedValue as $key => $received) {
                        if ($received['column'] === $filter) {
                            // Substituir os valores do registro existente
                            $this->receivedValue[$key] = $newValue;
                            $columnExists = true;
                            break;
                        }
                    }

                    // Se não houver registro com a mesma chave "column", adicione um novo registro
                    if (!$columnExists) {
                        $this->receivedValue[] = $newValue;
                    }
                }
            }
        }

        $query = $this->model::Query();

        if ($this->search) {
            $query->where($this->column, 'like', "%".$this->search."%");
        }

        if (!empty($this->receivedValue)) {
            foreach ($this->receivedValue as $receivedFilter) {
                $query->whereIn($receivedFilter['column'], $receivedFilter['values']);
            }
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
