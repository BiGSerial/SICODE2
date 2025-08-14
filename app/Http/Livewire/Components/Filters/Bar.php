<?php

namespace App\Http\Livewire\Components\Filters;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\{Arr, Str};
use Livewire\Component;

/**
 * Filter configuration example to use on Blade View to call Filters.Bar
 *
 * $filters = [
 *   [
 *     'key' => 'city',
 *     'label' => 'Município',
 *     'type' => 'multi',
 *     'provider' => [
 *       'type' => 'eloquent',
 *       'model' => \App\Models\Protest::class,
 *       'value' => 'cidade',
 *       'label' => 'cidade',
 *       'distinct' => true,
 *       'orderBy' => ['cidade' => 'asc'],
 *       'limit' => 300,
 *     ],
 *   ],
 *   [
 *     'key' => 'type',
 *     'label' => 'Tipo',
 *     'type' => 'single',
 *     'provider' => [
 *       'type' => 'static',
 *       'options' => [
 *         ['value' => 'OU', 'label' => 'Ouvidoria'],
 *         ['value' => 'NA', 'label' => 'Atendimento'],
 *         ['value' => 'PR', 'label' => 'Procon'],
 *       ]
 *     ]
 *   ],
 *   [
 *     'key' => 'search',
 *     'label' => 'Pesquisar Nota',
 *     'type' => 'text',
 *     'placeholder' => 'Nº da Nota...',
 *   ],
 *   [
 *     'key' => 'desired_between',
 *     'label' => 'Desejada (de/até)',
 *     'type' => 'daterange',
 *   ],
 * ];
 */

class Bar extends Component
{
    public $config = [];
    public $state = [];
    public $group = 'default';
    public $manualApply = true;
    public $search = [];
    public $open = null;

    public $listeners = [
        'filters.set' => 'setState',
        'filters.clear' => 'clearAll',
        'filters.reload' => '$refresh',
    ];


    public function mount(array $config, $group = 'default', $manualApply = true, $initial = [])
    {
        $this->config = $config;
        $this->group = $group;
        $this->manualApply = (bool) $manualApply;

        $persisted = session("filters.{$this->group}", []);
        $this->state = array_merge($persisted, $initial ?? []);

    }

    public function updatedState()
    {

        if (!$this->manualApply) {
            $this->persist();
            $this->emitUp('filters.updated', $this->payload());
        }
    }

    public function apply()
    {

        $this->persist();
        $this->emitUp('filters.applied', $this->payload());
        $this->emitUp('filters.updated', $this->payload());
        $this->open = null;
    }

    public function clear($key)
    {
        unset($this->state[$key]);
        $this->applyOrUpdate();
    }

    public function clearAll()
    {
        $this->state = [];
        $this->applyOrUpdate();
        $this->open = null;
    }

    public function setState($state)
    {

        $this->state = $state ?: [];
        $this->applyOrUpdate();
    }

    protected function applyOrUpdate()
    {
        $this->persist();
        $this->emitUp('filters.updated', $this->payload());
    }

    protected function persist(): void
    {
        session(["filters.{$this->group}" => $this->state]);
    }

    public function getOptions($key): array
    {
        $def = collect($this->config)->firstWhere('key', $key);
        if (!$def) {
            return [];
        }

        $cacheKey = $this->cacheKey($key);
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($def) {
            $provider = $def['provider'] ?? ['type' => 'static', 'options' => []];

            if (($provider['type'] ?? 'static') === 'static') {
                return $provider['options'] ?? [];
            }

            // eloquent provider
            $model = app($provider['model']);
            $value = $provider['value'] ?? 'id';
            $label = $provider['label'] ?? $value;

            $q = $model::query();

            foreach (($provider['where'] ?? []) as $w) {
                [$col, $op, $val] = $w;
                if (is_string($val) && Str::startsWith($val, ':state.')) {
                    $depKey = Str::after($val, ':state.');
                    $val = Arr::get($this->state, $depKey);
                    if ($val === null || $val === '' || $val === []) {
                        return [];
                    }
                }
                is_array($val) ? $q->whereIn($col, $val) : $q->where($col, $op, $val);
            }

            $select = [$value, $label];
            $q->select($select);

            if (!empty($provider['distinct'])) {
                $q->distinct();
            }

            foreach (($provider['orderBy'] ?? []) as $c => $dir) {
                $q->orderBy($c, $dir);
            }
            if (!empty($provider['limit'])) {
                $q->limit((int) $provider['limit']);
            }

            return $q->get()->map(function ($r) use ($value, $label) {
                return ['value' => $r->{$value}, 'label' => (string) $r->{$label}];
            })->values()->all();
        });
    }

    public function toggleState($key, $value)
    {
        // Obtém o valor atual para a chave, ou um array vazio se não existir
        $current = $this->state[$key] ?? [];

        if (is_array($current)) {
            // Se o valor já está no array, remove-o
            if (in_array($value, $current)) {
                $current = array_diff($current, [$value]);
            }
            // Caso contrário, adiciona-o
            else {
                $current[] = $value;
            }
        } else {
            // Se for um tipo 'single', simplesmente define o valor
            $current = $value;
        }

        // Atualiza a propriedade
        $this->state[$key] = $current;

        // Se não for manual, aplica ou atualiza imediatamente
        if (!$this->manualApply) {
            $this->applyOrUpdate();
        }
    }

    protected function cacheKey($key): string
    {
        $def = collect($this->config)->firstWhere('key', $key) ?? [];
        $depends = $def['dependsOn'] ?? [];
        $slice = Arr::only($this->state, $depends);
        return "filters:{$this->group}:{$key}:".md5(json_encode($slice));
    }

    protected function payload(): array
    {
        $out = [];
        foreach ($this->state as $k => $v) {
            if (is_array($v) && count($v) === 0) {
                continue;
            }
            if ($v === '' || $v === null) {
                continue;
            }
            $out[$k] = $v;
        }
        return $out;
    }

    public function render()
    {
        return view('livewire.components.filters.bar');
    }
}
