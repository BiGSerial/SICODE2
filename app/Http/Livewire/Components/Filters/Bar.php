<?php

namespace App\Http\Livewire\Components\Filters;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    /** @var array<string, array<int, array{value:mixed,label:string}>> */
    public $options = [];

    protected $listeners = [
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

    /* ---------- UX ---------- */

    public function openDropdown(string $key): void
    {
        $this->open = $this->open === $key ? null : $key;

        if ($this->open && !array_key_exists($key, $this->options)) {
            $this->options[$key] = $this->buildOptions($key);
        }
    }

    public function closeDropdown(): void
    {
        $this->open = null;
    }

    public function refreshDropdown(string $key): void
    {
        if ($this->open === $key) {
            $this->options[$key] = $this->buildOptions($key);
        }
    }

    /* ---------- Ciclo de atualização ---------- */

    public function updatedState()
    {
        if (! $this->manualApply) {
            $this->persist();
            $this->emitUp('filters.updated', $this->payload());
        }
    }

    public function apply()
    {
        $this->persist();
        $payload = $this->payload();
        $this->emitUp('filters.applied', $payload);
        $this->emitUp('filters.updated', $payload);
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

    protected function applyOrUpdate(): void
    {
        $this->persist();
        $this->emitUp('filters.updated', $this->payload());
    }

    protected function persist(): void
    {
        session(["filters.{$this->group}" => $this->state]);
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

    /* ---------- Montagem de opções (rápida e cacheada) ---------- */

    protected function buildOptions(string $key): array
    {
        $def = collect($this->config)->firstWhere('key', $key);
        if (! $def) {
            return [];
        }

        $provider = $def['provider'] ?? ['type' => 'static', 'options' => []];
        $needle   = trim(strtolower($this->search[$key] ?? '')); // busca atual
        $depends  = $def['dependsOn'] ?? [];
        $slice    = Arr::only($this->state, $depends);

        $cacheKey = $this->cacheKey($key, $needle, $slice);

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($provider, $needle, $slice) {
            if (($provider['type'] ?? 'static') === 'static') {
                $opts = $provider['options'] ?? [];
                if ($needle !== '') {
                    $opts = array_values(array_filter(
                        $opts,
                        fn ($o) =>
                        Str::contains(strtolower((string)($o['label'] ?? '')), $needle)
                    ));
                }
                return array_slice($opts, 0, (int)($provider['limit'] ?? 300));
            }

            // ----- Eloquent -----
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = app($provider['model']);
            $value = $provider['value'] ?? 'id';
            $label = $provider['label'] ?? $value;

            $q = $model::query();

            // dependsOn / where dinâmico
            foreach (($provider['where'] ?? []) as $w) {
                [$col, $op, $val] = $w;
                if (is_string($val) && Str::startsWith($val, ':state.')) {
                    $depKey = Str::after($val, ':state.');
                    $val = Arr::get($this->state, $depKey);
                    if ($val === null || $val === '' || $val === []) {
                        return []; // dependência vazia => sem opções
                    }
                }
                is_array($val) ? $q->whereIn($col, $val) : $q->where($col, $op, $val);
            }

            // busca server-side (use 'like' em MySQL/MariaDB; 'ilike' no Postgres)
            if ($needle !== '') {
                $driver = $q->getModel()->getConnection()->getDriverName();
                $op = $driver === 'pgsql' ? 'ilike' : 'like';
                $q->where($label, $op, "%{$needle}%");
            }

            // select e “distinct”
            // Prefira GROUP BY para permitir uso de índices junto com ORDER/LIMIT
            $q->select([$value, $label])
              ->groupBy($value, $label);

            // order
            foreach (($provider['orderBy'] ?? []) as $c => $dir) {
                $q->orderBy($c, $dir);
            }

            // limit
            $q->limit((int)($provider['limit'] ?? 300));

            // retorno simples
            return $q->get()->map(fn ($r) => [
                'value' => $r->{$value},
                'label' => (string) $r->{$label},
            ])->values()->all();
        });
    }

    protected function cacheKey(string $key, string $needle, array $slice): string
    {
        return "filters:{$this->group}:{$key}:".md5($needle.'|'.json_encode($slice));
    }

    public function toggleState($key, $value)
    {
        $current = $this->state[$key] ?? [];

        if (is_array($current)) {
            // toggling stricto
            $exists = in_array($value, $current, true);
            $current = $exists ? array_values(array_diff($current, [$value])) : [...$current, $value];
        } else {
            $current = $value; // single
        }

        $this->state[$key] = $current;

        if (! $this->manualApply) {
            $this->applyOrUpdate();
        }
    }

    public function render()
    {
        return view('livewire.components.filters.bar');
    }
}
