<?php

namespace App\Http\Livewire\Components\Filter;

use Livewire\Component;

class Dropdown extends Component
{
    public string $filterKey;
    public array  $def = [];
    public $current;

    public array $options = [];
    public bool  $opened  = false;

    protected $listeners = [
        'dropdown:refresh'       => 'refreshIfMatches',
        'filters:optionsLoaded'  => 'receiveOptions', // recebe opções do pai
    ];

    public function mount(string $filterKey, array $def, $current = null)
    {
        $this->filterKey = $filterKey;
        $this->def       = $def;
        $this->current   = ($def['type'] ?? 'single') === 'multi'
            ? (array) ($current ?? [])
            : ($current ?? null);
    }

    public function render()
    {
        return view('livewire.components.filter.dropdown');
    }

    public function open()
    {
        $this->opened = true;
        // pede ao PAI para enviar opções (pai tem as Closures)
        $this->emitUp('filters:fetchOptions', $this->filterKey);
    }

    public function receiveOptions(string $key, array $options)
    {
        if ($key !== $this->filterKey) {
            return;
        }
        $this->options = $options;
    }

    public function refreshIfMatches(string $key)
    {
        if ($key === $this->filterKey && $this->opened) {
            // limpa seleção local se quiser:
            $this->current = ($this->def['type'] ?? 'single') === 'multi' ? [] : null;
            $this->emitUp('filters:fetchOptions', $this->filterKey);
        }
    }

    public function toggleValue($value)
    {
        $type = $this->def['type'] ?? 'single';
        if ($type === 'multi') {
            $arr = (array) $this->current;
            $i = array_search($value, $arr, true);
            if ($i !== false) {
                unset($arr[$i]);
            } else {
                $arr[] = $value;
            }
            $this->current = array_values($arr);
        } else {
            $this->current = $value;
        }
        $this->emitUp('filter:changed', $this->filterKey, $this->current);
    }

    public function clear()
    {
        $this->current = ($this->def['type'] ?? 'single') === 'multi' ? [] : null;
        $this->emitUp('dropdown:cleared', $this->filterKey);
    }
}
