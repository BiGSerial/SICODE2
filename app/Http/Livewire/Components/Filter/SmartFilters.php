<?php

namespace App\Http\Livewire\Components\Filter;

use App\Services\Filters\FilterRegistry;
use Livewire\Component;

class SmartFilters extends Component
{
    public array $config = [];  // com Closures aqui no PAI
    public array $filters = [];

    protected $listeners = [
        'filter:changed'      => 'onFilterChanged',
        'dropdown:opened'     => 'onDropdownOpened',
        'dropdown:cleared'    => 'onDropdownCleared',
        'filters:fetchOptions' => 'onFetchOptions',   // <-- novo
    ];

    public function mount(array $config = [], array $initial = [])
    {
        $this->config  = $config;
        $this->filters = $initial;
    }

    public function render()
    {
        return view('livewire.components.filter.smart-filters');
    }

    public function onFetchOptions(string $key)
    {
        $registry = new FilterRegistry($this->config, $this->filters);
        $options  = $registry->getOptions($key); // resolve usando as Closures aqui no PAI
        // Envia para TODOS os dropdowns; cada um checa se a mensagem é dele:
        $this->emitTo('components.filter.dropdown', 'filters:optionsLoaded', $key, $options);
    }

    public function onFilterChanged(string $key, $value)
    {
        $def = $this->config[$key] ?? [];
        $this->filters[$key] = ($def['type'] ?? 'single') === 'multi' ? (array) $value : ($value ?? null);

        // Limpa e atualiza dependentes
        foreach ($this->config as $k => $d) {
            if (!empty($d['depends_on']) && in_array($key, (array) $d['depends_on'], true)) {
                $this->filters[$k] = ($d['type'] ?? 'single') === 'multi' ? [] : null;
                $this->emitTo('components.filter.dropdown', 'dropdown:refresh', $k);
            }
        }

        $this->emitUp('filters:updated', $this->filters);
    }

    public function onDropdownOpened(string $key)
    {
        // opcional: marcar aberto; aqui já pode forçar fetch
        $this->onFetchOptions($key);
    }

    public function onDropdownCleared(string $key)
    {
        $def = $this->config[$key] ?? [];
        $this->filters[$key] = ($def['type'] ?? 'single') === 'multi' ? [] : null;
        $this->emitUp('filters:updated', $this->filters);

        foreach ($this->config as $k => $d) {
            if (!empty($d['depends_on']) && in_array($key, (array) $d['depends_on'], true)) {
                $this->filters[$k] = ($d['type'] ?? 'single') === 'multi' ? [] : null;
                $this->emitTo('components.filter.dropdown', 'dropdown:refresh', $k);
            }
        }
    }
}
