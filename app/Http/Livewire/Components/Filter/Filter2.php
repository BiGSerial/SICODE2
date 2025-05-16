<?php

namespace App\Http\Livewire\Components\Filter;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Filter2 extends Component
{
    public $model;
    // Coluna que popula o <option value="...">
    public string  $column;
    // Coluna que aparece no label do <option>
    public string  $displayColumn;
    // Coluna onde o campo de search interno já filtrava
    public string  $searchColumn;
    // Coluna que o filtro vai enviar para o receptor (send_filter)
    public string  $sendSearchColumn;

    public string  $direction;
    public string  $groupFilter;
    public string  $filterLabel;
    public string  $receiverKey;
    public ?string $sendFilter;
    public ?string $customQuery;
    public ?string $customBuilderMethod;
    public string  $myKey;

    public array   $items         = [];
    public array   $receivedValue = [];
    public bool    $isRefreshing  = false;
    public string  $search        = '';

    protected $listeners = [
        'refresh_filter'     => 'refreshMe',
        'refresh_myself'     => '$refresh',
        'refresh_all_filter' => 'refreshAll',
        'toUpdate'           => 'toUpdate',
    ];

    /**
     * @param string      $myKey               chave única deste filtro
     * @param string|null $sendFilter          myKey do outro filtro que deve receber
     * @param string      $modelClass          FQN do Model, ex: App\Models\User::class
     * @param string      $column              coluna que será o value do dropdown
     * @param string      $filterLabel         texto do botão do filtro
     * @param string      $groupFilter         sessão para agrupar filtros
     * @param string      $displayColumn       coluna mostrada no dropdown
     * @param string      $direction           ASC ou DESC
     * @param string|null $customQuery         cláusula raw extra (whereRaw)
     * @param string|null $searchColumn        coluna usada no campo de busca interna
     * @param string|null $sendSearchColumn    coluna de destino no filtro receptor
     * @param string|null $customBuilderMethod método para customizar o Builder
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
        $this->receiverKey         = $myKey;
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
    }

    public function applyFilter()
    {
        // salva no session
        session([
            "filter.{$this->groupFilter}.{$this->myKey}" => $this->items,
        ]);

        // notifica lista pai e cascata pro receptor
        $payload = [
            'column'       => $this->column,
            'values'       => $this->items,
            'targetColumn' => $this->sendSearchColumn,
        ];
        $this->emitUp('refresh_list');
        $this->emit('refresh_filter', $this->sendFilter, $payload);
    }

    public function removeFilter()
    {
        session()->forget("filter.{$this->groupFilter}.{$this->myKey}");
        $this->items = [];

        $payload = ['column' => $this->column, 'values' => [], 'targetColumn' => $this->sendSearchColumn];
        $this->emitUp('refresh_list');
        $this->emit('refresh_filter', $this->sendFilter, $payload);
    }

    public function refreshMe($mkey, $payload = [])
    {
        if ($mkey !== $this->receiverKey) {
            return;
        }

        $this->isRefreshing = true;

        // payload deve ter column, values e targetColumn
        if (!empty($payload['values'])) {
            // atualiza receivedValue substituindo mesma coluna
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
            // limpeza de filtro cascata
            $this->receivedValue = array_filter($this->receivedValue, fn ($rec) => $rec['column'] !== $payload['column']);
        }

        $this->emitSelf('refresh_myself');
        $this->isRefreshing = false;
    }

    public function refreshAll()
    {
        $this->items = session("filter.{$this->groupFilter}.{$this->column}", []);
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
     * Aqui montamos a query final do dropdown
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

        // aplica filtros recebidos em cascade
        foreach ($this->receivedValue as $rec) {
            $col = $rec['targetColumn'] ?? $rec['column'];
            $query->whereIn($col, $rec['values']);
        }

        // hook custom via método
        if ($this->customBuilderMethod && method_exists($this, $this->customBuilderMethod)) {
            $query = $this->{$this->customBuilderMethod}($query);
        }

        // agrupamento e ordenação
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
        return view('livewire.components.filter.filter', [
            'filterLists' => $this->filterLists,
        ]);
    }
}
