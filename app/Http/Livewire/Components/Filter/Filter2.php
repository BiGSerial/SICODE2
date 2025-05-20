<?php

namespace App\Http\Livewire\Components\Filter;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Filter2 extends Component
{
    // instância do Model para consultas
    public $model = null;

    // Coluna que popula o <option value="...">
    public string $column = '';
    // Coluna que aparece no label do <option>
    public string $displayColumn = '';
    // Coluna onde o campo de search interno já filtrava
    public string $searchColumn = '';
    // Coluna que o filtro vai enviar para o receptor (send_filter)
    public string $sendSearchColumn = '';

    public string  $direction = 'ASC';
    public string  $groupFilter = '';
    public string  $filterLabel = '';
    public ?string  $receiverKey = '';
    public ?string $sendFilter = null;
    public ?string $customQuery = null;
    public ?string $customBuilderMethod = null;
    public string  $myKey = '';

    // guarda filtros que vieram de outros componentes (cascade)
    public array   $receivedValue = [];

    public array   $items = [];
    public bool    $isRefreshing = false;
    public string  $search = '';

    protected $listeners = [
        'refresh_filter'     => 'refreshMe',
        'refresh_myself'     => '$refresh',
        'refresh_All_Filter' => 'refreshAll',
        'toUpdate'           => 'toUpdate',
    ];

    /**
     * Inicializa o componente, popula sessão e cascade filters
     */
    public function mount(
        string  $myKey,
        ?string $sendFilter,
        string  $modelClass,
        string  $column,
        string  $filterLabel,
        string  $groupFilter,
        string  $displayColumn,
        string  $direction = 'ASC',
        ?string $customQuery = null,
        ?string $searchColumn = null,
        ?string $sendSearchColumn = null,
        ?string $customBuilderMethod = null
    ) {
        // instancia model
        $this->model               = app($modelClass);
        $this->myKey               = $myKey;
        // $this->receiverKey         = $receiverKey;
        $this->sendFilter          = $sendFilter;
        $this->column              = $column;
        $this->filterLabel         = $filterLabel;
        $this->groupFilter         = $groupFilter;
        $this->displayColumn       = $displayColumn;
        $this->direction           = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->customQuery         = $customQuery;
        $this->searchColumn        = $searchColumn ?? $displayColumn;
        $this->sendSearchColumn    = $sendSearchColumn ?? $column;
        $this->customBuilderMethod = $customBuilderMethod;

        // carregar sessão existente
        if (!session()->isStarted()) {
            session()->start();
        }
        $this->items = session("filter.{$this->groupFilter}.{$this->myKey}", []);

        // registra cascata pra receptor
        if ($this->sendFilter) {
            session()->push("filter.{$this->groupFilter}.receiver.{$this->sendFilter}", $this->column);
        }

        // monta receivedValue com filtros já aplicados
        $receivers = session("filter.{$this->groupFilter}.receiver.{$this->receiverKey}", []);



        foreach ($receivers as $col) {
            $vals = session("filter.{$this->groupFilter}.{$col}", []);
            if (!empty($vals)) {
                $this->receivedValue[] = [
                    'column'       => $col,
                    'values'       => $vals,
                    'targetColumn' => $this->sendSearchColumn,
                ];
            }
        }
    }

    // aplica o filtro atual e notifica receptor
    public function applyFilter()
    {
        session([
            "filter.{$this->groupFilter}.{$this->myKey}" => $this->items,
        ]);

        $payload = [
            'column'       => $this->column,
            'values'       => $this->items,
            'targetColumn' => $this->sendSearchColumn,
        ];


        $this->emitUp('refresh_list');
        $this->emit('refresh_filter', $this->sendFilter, $payload);
    }

    // limpa este filtro e notifica receptor
    public function removeFilter()
    {
        session()->forget("filter.{$this->groupFilter}.{$this->myKey}");
        $this->items = [];

        $payload = ['column' => $this->column, 'values' => [], 'targetColumn' => $this->sendSearchColumn];
        $this->emitUp('refresh_list');
        $this->emit('refresh_filter', $this->sendFilter, $payload);
    }

    /**
     * Recebe payload dos filtros de cascata
     */
    public function refreshMe($receiverKey, $payload = [])
    {

        if ($receiverKey !== $this->myKey) {
            return;
        } else {
            $this->receiverKey = $receiverKey;
        }

        //  dd($receiverKey, $payload);

        $this->isRefreshing = true;

        if (!empty($payload['values'])) {
            $exists = false;
            foreach ($this->receivedValue as $i => $rec) {
                if ($rec['column'] === $payload['column']) {
                    $this->receivedValue[$i] = $payload;
                    $exists = true;
                    break;
                }
            }
            if (! $exists) {
                $this->receivedValue[] = $payload;
            }
        } else {
            $this->receivedValue = array_filter(
                $this->receivedValue,
                fn ($rec) => $rec['column'] !== $payload['column']
            );
        }

        $this->items = $this->filterLists->pluck($this->column)->toArray();
        $this->emitSelf('refresh_myself');
        $this->isRefreshing = false;

    }

    public function refreshAll()
    {

        $this->items = [];
        $this->emitSelf('refresh_myself');


    }

    public function toUpdate($mkey)
    {
        if ($mkey !== $this->myKey) {
            return;
        }
        $this->items = session("filter.{$this->groupFilter}.{$this->myKey}", []);
    }

    /**
     * Constrói o dropdown com filtros internos + cascade
     */
    public function getFilterListsProperty()
    {
        /** @var Builder $query */
        $query = $this->model::query();

        // busca interna
        if ($this->search) {
            $query->where($this->searchColumn, 'like', "%{$this->search}%");
        }

        // raw extra
        if ($this->customQuery) {
            $query->whereRaw($this->customQuery);
        }

        // aplica cascade filters
        foreach ($this->receivedValue as $rec) {
            $col = $rec['targetColumn'] ?? $rec['column'];
            $query->whereIn($col, $rec['values']);
        }

        // hook custom
        if ($this->customBuilderMethod && method_exists($this, $this->customBuilderMethod)) {
            $query = $this->{$this->customBuilderMethod}($query);
        }

        $query->orderBy($this->displayColumn, $this->direction);
        $selects = [$this->column];
        if ($this->displayColumn !== $this->column) {
            $selects[] = $this->displayColumn;
        }
        $query->select($selects)->groupBy($selects);

        return $query->get();
    }

    public function render()
    {
        return view('livewire.components.filter.filter2', [
            'filterLists' => $this->filterLists,
        ]);
    }
}
