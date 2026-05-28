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

    public $custom_query;

    public $myKey;

    protected $listeners = [
        'refresh_filter'     => 'refreshme',
        'refresh_myself'     => '$refresh',
        'refresh_All_Filter' => 'refreshAll',
        'toUpdate'          => 'toUpdate',

    ];

    /**
     * Undocumented function
     *
     * @param [string] $myKey Exclusive name filter of this filter.
     * @param [string] $sendFilter If has others filters, give a 'myKey' of a filter associate with this filter .
     * @param [string] $model Model of Filter EX: "/App/Models/User"
     * @param [string] $column A exclusive value to filter Search
     * @param [string] $filter A text to show in Button Fiilter
     * @param [string] $group_filter A Group Filter to $_SESSION['filter']['group_filter_name']
     * @param [string] $values A value Column to show in Fiter List
     * @param [string] $direction Order list Filter 'ASC' Ascending (Default) or 'DESC' Descending
     * @param [string] $query Add a Custom Query EX. "where('column', 'value')->where('column2', 'value2')"
     * @return void
     */
    public function mount($myKey, $sendFilter, $model, $column, $filter, $group_filter, $values, $direction, $query)
    {
        $this->model        = app($model);
        $this->column       = $column;
        $this->filter       = $filter;
        $this->group_filter = $group_filter;
        $this->values       = $values;
        $this->direction    = $direction;
        $this->receiverKey  = $myKey;
        $this->sendFilter   = $sendFilter;
        $this->custom_query = $query;
        $this->myKey        = $myKey;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $persistedItems = session('filter.' . $this->group_filter . '.' . $this->myKey);
        if (is_array($persistedItems)) {
            $this->items = $persistedItems;
            $_SESSION['filter'][$this->group_filter][$this->myKey] = $persistedItems;
        } elseif (isset($_SESSION['filter'][$this->group_filter][$this->myKey])) {
            $this->items = $_SESSION['filter'][$this->group_filter][$this->myKey];
        } else {
            $this->items = [];
        }

        if (
            $this->sendFilter
            && (
                !isset($_SESSION['filter'][$this->group_filter]['receiver'][$this->sendFilter])
                || !in_array($this->column, $_SESSION['filter'][$this->group_filter]['receiver'][$this->sendFilter])
            )
        ) {
            $_SESSION['filter'][$this->group_filter]['receiver'][$this->sendFilter][] = $this->column;
            session([
                'filter.' . $this->group_filter . '.receiver.' . $this->sendFilter
                => $_SESSION['filter'][$this->group_filter]['receiver'][$this->sendFilter],
            ]);
        }

    }

    public function toUpdate($mkey)
    {
        if ($mkey == $this->myKey) {

            if (!(session_status() == PHP_SESSION_ACTIVE)) {
                if (!session()->isStarted()) { session()->start(); }
            }

            if (isset($_SESSION['filter'][$this->group_filter][$this->myKey])) {
                # code...
            }

            $this->items = $_SESSION['filter'][$this->group_filter][$this->myKey];
        }
    }

    public function refreshAll()
    {
        if (isset($_SESSION['filter'][$this->group_filter][$this->column])) {
            $this->items = $_SESSION['filter'][$this->group_filter][$this->column];
        } else {
            $this->items = [];
        }

        $this->emitSelf('refresh_myself');
    }

    public function refreshme($myKey, $values = [])
    {


        if ($this->receiverKey === $myKey) {

            $this->isRefreshing = true;

            if (!empty($values)) {

                $columnExists = false;
                $newValue     = $values;

                // Verificar se já existe um registro com a mesma chave "column"
                foreach ($this->receivedValue as $key => $received) {
                    if ($received['column'] === $newValue['column']) {
                        // Substituir os valores do registro existente
                        $this->receivedValue[$key] = $newValue;
                        $columnExists              = true;

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
                $this->items         = [];
                $this->applyFilter();
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
            if (!session()->isStarted()) { session()->start(); }
        }

        $_SESSION['filter'][$this->group_filter][$this->myKey] = $this->items;
        session(['filter.' . $this->group_filter . '.' . $this->myKey => $this->items]);

        $this->emit('refresh_filter', $this->sendFilter, ['column' => $this->column, 'values' => $this->items]);

        // Evita corrida de atualização em filtros encadeados:
        // o refresh da lista deve ocorrer no último filtro da cadeia.
        if (!$this->sendFilter) {
            $this->emitUp('refresh_list');
        }
    }

    public function removeFilter()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->group_filter][$this->myKey])) {
            unset($_SESSION['filter'][$this->group_filter][$this->myKey]);
            // unset($_SESSION['filter'][$this->group_filter]['receiver']);
            $this->items = [];
        }
        session()->forget('filter.' . $this->group_filter . '.' . $this->myKey);

        $this->emit('refresh_filter', $this->sendFilter);

        // Mesma regra da aplicação: refresh apenas no último filtro.
        if (!$this->sendFilter) {
            $this->emitUp('refresh_list');
        }
    }

    public function getListFilterProperty()
    {
        if (isset($_SESSION['filter'][$this->group_filter]['receiver'][$this->receiverKey])) {

            foreach ($_SESSION['filter'][$this->group_filter]['receiver'][$this->receiverKey] as $filter) {
                if (isset($_SESSION['filter'][$this->group_filter][$filter])) {
                    $columnExists = false;
                    $newValue     = [
                        'column' => $filter,
                        'values' => $_SESSION['filter'][$this->group_filter][$filter],
                    ];

                    // Verificar se já existe um registro com a mesma chave "column"
                    foreach ($this->receivedValue as $key => $received) {
                        if ($received['column'] === $filter) {
                            // Substituir os valores do registro existente
                            $this->receivedValue[$key] = $newValue;
                            $columnExists              = true;

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
            $query->where($this->column, 'like', '%' . $this->search . '%');
        }

        if ($this->custom_query) {
            $query->whereRaw($this->custom_query);
        }

        if (!empty($this->receivedValue)) {
            foreach ($this->receivedValue as $receivedFilter) {
                $query->whereIn($receivedFilter['column'], $receivedFilter['values']);
            }
        }
        $query->orderBy($this->values, $this->direction);

        if ($this->column != $this->values) {
            $query->select($this->column, $this->values)
                ->groupBy($this->column, $this->values);
        } else {
            $query->select($this->column)
                ->groupBy($this->column);
        }

        return $query->get();
    }

    public function render()
    {
        return view('livewire.components.filter.filter', [
            'filterLists' => $this->listFilter,
        ]);
    }
}
